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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Neos\Cqrs\Domain\Timestamp;
use Neos\Cqrs\Event\EventTransport;
use Neos\Cqrs\Event\EventTypeResolver;
use Neos\Cqrs\EventStore\EventStreamData;
use Neos\Cqrs\EventStore\EventStreamFilter;
use Neos\Cqrs\EventStore\Exception\ConcurrencyException;
use Neos\Cqrs\EventStore\Storage\Doctrine\DataTypes\DateTimeType;
use Neos\Cqrs\EventStore\Storage\Doctrine\Factory\ConnectionFactory;
use Neos\Cqrs\EventStore\Storage\EventStorageInterface;
use Neos\Cqrs\Message\MessageMetadata;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfiguration;

/**
 * Database event storage, for testing purpose
 */
class DoctrineEventStorage implements EventStorageInterface
{
    /**
     * @Flow\Inject
     * @var ConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject
     * @var EventTypeResolver
     */
    protected $eventTypeResolver;

    /**
     * @param EventStreamFilter $filter
     * @return EventStreamData
     */
    public function load(EventStreamFilter $filter)
    {
        $conn = $this->connectionFactory->get();
        $queryBuilder = $conn->createQueryBuilder();
        $query = $queryBuilder
            ->select('type, event, metadata')
            ->from($this->connectionFactory->getStreamTableName())
            ->orderBy('commit_version', 'ASC')
            ->addOrderBy('event_version', 'ASC');
        if ($filter->streamName !== null) {
            $queryBuilder->andWhere('stream_name = :stream_name');
            $queryBuilder->setParameter('stream_name', $filter->streamName);
        } elseif ($filter->streamNamePrefix !== null) {
            $queryBuilder->andWhere('stream_name LIKE :stream_name_prefix');
            $queryBuilder->setParameter('stream_name_prefix', $filter->streamNamePrefix . '%');
        }
        if ($filter->eventTypes !== null) {
            $queryBuilder->andWhere('type IN (:event_types)');
            $queryBuilder->setParameter('event_types', $filter->eventTypes, Connection::PARAM_STR_ARRAY);
        }

        $data = $this->unserializeEvents($query);

        if ($data === []) {
            return null;
        }

        return new EventStreamData($data, 0);
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
                'commit_version' => ':commit_version',
                'event_version' => ':event_version',
                'type' => ':type',
                'event' => ':event',
                'metadata' => ':metadata',
                'recorded_at' => ':recorded_at'
            ])
            ->setParameters([
                'stream_name' => $streamName,
                'commit_version' => $commitVersion,
                'recorded_at' => $now,
            ], [
                'stream_name' => \PDO::PARAM_STR,
                'version' => \PDO::PARAM_INT,
                'type' => \PDO::PARAM_STR,
                'event' => \PDO::PARAM_STR,
                'metadata' => \PDO::PARAM_STR,
                'recorded_at' => DateTimeType::DATETIME_MICRO,
            ]);

        $version = 1;
        array_map(function (EventTransport $eventTransport) use ($query, &$version) {
            $convertedEvent = $this->propertyMapper->convert($eventTransport->getEvent(), 'array');

            $serializedEvent = json_encode($convertedEvent, JSON_PRETTY_PRINT);
            $convertedMetadata = $this->propertyMapper->convert($eventTransport->getMetadata(), 'array');
            $serializedMetadata = json_encode($convertedMetadata, JSON_PRETTY_PRINT);
            $query->setParameter('event_version', $version);

            $query->setParameter('type', $this->eventTypeResolver->getEventType($eventTransport->getEvent()));
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
            ->andWhere('stream_name = :stream_name')
            ->orderBy('commit_version', 'DESC')
            ->addOrderBy('event_version', 'DESC')
            ->setMaxResults(1)
            ->setParameter('stream_name', $streamName);

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
        $configuration->forProperty('*')->allowAllProperties();

        $data = [];
        foreach ($query->execute()->fetchAll() as $stream) {
            $unserializedEvent = json_decode($stream['event'], true);
            $unserializedMetadata = json_decode($stream['metadata'], true);
            $eventClassName = $this->eventTypeResolver->getEventClassNameByType($stream['type']);
            $data[] = new EventTransport(
                $this->propertyMapper->convert($unserializedEvent, $eventClassName, $configuration),
                $this->propertyMapper->convert($unserializedMetadata, MessageMetadata::class, $configuration)
            );
        }
        return $data;
    }
}
