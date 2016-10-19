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
use Neos\Cqrs\Event\EventTransport;
use Neos\Cqrs\EventStore\EventStreamData;
use Neos\Cqrs\EventStore\Exception\ConcurrencyException;
use Neos\Cqrs\EventStore\Storage\Doctrine\Factory\ConnectionFactory;
use Neos\Cqrs\EventStore\Storage\EventStorageInterface;
use Neos\Cqrs\Message\MessageMetadata;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Utility\Now;
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
     * @Flow\Inject
     * @var Now
     */
    protected $now;

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
            ->select('*')
            ->from($this->connectionFactory->getStreamTableName())
            ->andWhere('stream = :stream')
            ->orderBy('id', 'ASC')
            ->addOrderBy('version', 'ASC')
            ->setParameter('stream', $streamName, \PDO::PARAM_STR);

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

        $query = $queryBuilder
            ->insert($this->connectionFactory->getStreamTableName())
            ->values([
                'stream' => ':stream',
                'version' => ':version',
                'type' => ':type',
                'payload' => ':payload',
                'metadata' => ':metadata',
                'recordedat' => ':recordedat'
            ])
            ->setParameters([
                'stream' => $streamName,
                'version' => $commitVersion,
                'recordedat' => $this->now->format(DATE_ISO8601)
            ], [
                'stream' => \PDO::PARAM_STR,
                'version' => \PDO::PARAM_INT,
                'type' => \PDO::PARAM_STR,
                'event' => \PDO::PARAM_STR,
                'metadata' => \PDO::PARAM_STR,
                'recordedat' => \PDO::PARAM_STR,
            ]);

        array_map(function (EventTransport $eventTransport) use ($query) {
            $convertedEvent = $this->propertyMapper->convert($eventTransport->getEvent(), 'array');
            $serializedPayload = json_encode($convertedEvent, JSON_PRETTY_PRINT);
            $convertedMetadata = $this->propertyMapper->convert($eventTransport->getMetadata(), 'array');
            $serializedMetadata = json_encode($convertedMetadata, JSON_PRETTY_PRINT);

            $query->setParameter('type', TypeHandling::getTypeForValue($eventTransport->getEvent()));
            $query->setParameter('payload', $serializedPayload);
            $query->setParameter('metadata', $serializedMetadata);

            $query->execute();
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
            ->select('version')
            ->from($this->connectionFactory->getStreamTableName())
            ->andWhere('stream = :stream')
            ->orderBy('version', 'DESC')
            ->setMaxResults(1)
            ->setParameter('stream', $streamName);

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
            $unserializedEvent = json_decode($stream['payload'], true);
            $unserializedMetadata = json_decode($stream['metadata'], true);
            $data[] = new EventTransport(
                $this->propertyMapper->convert($unserializedEvent, $stream['type'], $configuration),
                $this->propertyMapper->convert($unserializedMetadata, MessageMetadata::class, $configuration)
            );
        }
        return $data;
    }
}
