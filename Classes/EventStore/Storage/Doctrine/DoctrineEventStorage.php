<?php
namespace Neos\Cqrs\EventStore\Storage\Doctrine;

/*
 * This file is part of the Neos.EventStore.DatabaseStorageAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Query\QueryBuilder;
use Neos\Cqrs\Domain\Timestamp;
use Neos\Cqrs\Event\EventTransport;
use Neos\Cqrs\EventStore\EventStreamData;
use Neos\Cqrs\EventStore\Exception\ConcurrencyException;
use Neos\Cqrs\EventStore\Storage\Doctrine\DataTypes\DateTimeType;
use Neos\Cqrs\EventStore\Storage\Doctrine\Factory\ConnectionFactory;
use Neos\Cqrs\EventStore\Storage\EventStorageInterface;
use Neos\Cqrs\Message\MessageMetadata;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Property\TypeConverter\ObjectConverter;
use TYPO3\Flow\Utility\TypeHandling;

/**
 * Database event storage, for testing purpose
 */
class DoctrineEventStorage implements EventStorageInterface
{
    /**
     * @var ConnectionFactory
     * @Flow\Inject
     */
    protected $connectionFactory;

    /**
     * @var PropertyMapper
     * @Flow\Inject
     */
    protected $propertyMapper;

    /**
     * @var array
     */
    protected $runtimeCache = [];

    /**
     * @param string $streamName
     * @return EventStreamData
     */
    public function load(string $streamName)
    {
        $version = $this->getCurrentVersion($streamName);
        $cacheKey = md5($streamName . '.' . $version);
        if (isset($this->runtimeCache[$cacheKey])) {
            return $this->runtimeCache[$cacheKey];
        }
        $conn = $this->connectionFactory->get();
        $queryBuilder = $conn->createQueryBuilder();
        $query = $queryBuilder
            ->select('type, event, metadata')
            ->from($this->connectionFactory->getStreamTableName())
            ->andWhere('stream_name_hash = :stream_name_hash')
            ->orderBy('commit_version', 'ASC')
            ->addOrderBy('event_version', 'ASC')
            ->setParameter('stream_name_hash', md5($streamName));

        $data = $this->unserializeEvents($query);

        if ($data === []) {
            return null;
        }

        $cacheKey = md5($streamName . '.' . $version);
        $this->runtimeCache[$cacheKey] = new EventStreamData($data, $version);

        return $this->runtimeCache[$cacheKey];
    }

    /**
     * @param string $streamName
     * @param array $data
     * @param int $commitVersion
     * @param \Closure $callback
     * @return int
     * @throws ConcurrencyException|\Exception
     */
    public function commit(string $streamName, array $data, int $commitVersion, \Closure $callback = null)
    {
        $stream = new EventStreamData($data, $commitVersion);
        $connection = $this->connectionFactory->get();
        if ($callback !== null) {
            $connection->beginTransaction();
        }

        $queryBuilder = $connection->createQueryBuilder();

        $now = Timestamp::create();

        $query = $queryBuilder
            ->insert($this->connectionFactory->getStreamTableName())
            ->values([
                'stream_name' => ':stream_name',
                'stream_name_hash' => ':stream_name_hash',
                'commit_version' => ':commit_version',
                'event_version' => ':event_version',
                'type' => ':type',
                'event' => ':event',
                'metadata' => ':metadata',
                'recorded_at' => ':recorded_at'
            ])
            ->setParameters([
                'stream_name' => $streamName,
                'stream_name_hash' => md5($streamName),
                'commit_version' => $commitVersion,
                'recorded_at' => $now,
            ], [
                'stream_name' => \PDO::PARAM_STR,
                'stream_name_hash' => \PDO::PARAM_STR,
                'version' => \PDO::PARAM_INT,
                'type' => \PDO::PARAM_STR,
                'event' => \PDO::PARAM_STR,
                'metadata' => \PDO::PARAM_STR,
                'recorded_at' => DateTimeType::DATETIME_MICRO,
            ]);

        $version = 1;
        array_map(function (EventTransport $eventTransport) use ($query, &$version) {
            $convertedEvent = $this->propertyMapper->convert($eventTransport->getEvent(), 'array');

            $serializedEvent = json_encode($convertedEvent);
            $convertedMetadata = $this->propertyMapper->convert($eventTransport->getMetadata(), 'array');
            $serializedMetadata = json_encode($convertedMetadata);
            $query->setParameter('event_version', $version);

            // the format should be "<BoundedContext>:<EventType>"
            $query->setParameter('type', TypeHandling::getTypeForValue($eventTransport->getEvent()));
            $query->setParameter('event', $serializedEvent);
            $query->setParameter('metadata', $serializedMetadata);

            $query->execute();
            $version++;
        }, $stream->getData());

        if ($callback !== null) {
            try {
                $callback($commitVersion);
                $connection->commit();
            } catch (\Exception $exception) {
                $connection->rollBack();
                throw $exception;
            }
        }

        return $commitVersion;
    }

    /**
     * @param  string $streamName
     * @return integer Current Aggregate Root version
     */
    public function getCurrentVersion(string $streamName): int
    {
        $conn = $this->connectionFactory->get();
        $queryBuilder = $conn->createQueryBuilder();
        $query = $queryBuilder
            ->select('commit_version')
            ->from($this->connectionFactory->getStreamTableName())
            ->andWhere('stream_name_hash = :stream_name_hash')
            ->orderBy('commit_version', 'DESC')
            ->addOrderBy('event_version', 'DESC')
            ->setMaxResults(1)
            ->setParameter('stream_name_hash', md5($streamName));

        $version = (integer)$query->execute()->fetchColumn();
        return $version ?: 0;
    }

    /**
     * @param QueryBuilder $query
     * @return array
     */
    protected function unserializeEvents(QueryBuilder $query): array
    {
        $configuration = new PropertyMappingConfiguration();
        $configuration->allowAllProperties();
        $configuration->setTypeConverterOption(
            ObjectConverter::class,
            ObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED,
            true
        );

        $data = [];
        foreach ($query->execute()->fetchAll() as $stream) {
            $unserializedEvent = json_decode($stream['event'], true);
            $unserializedMetadata = json_decode($stream['metadata'], true);
            $data[] = new EventTransport(
                $this->propertyMapper->convert($unserializedEvent, $stream['type'], $configuration),
                $this->propertyMapper->convert($unserializedMetadata, MessageMetadata::class, $configuration)
            );
        }
        return $data;
    }
}
