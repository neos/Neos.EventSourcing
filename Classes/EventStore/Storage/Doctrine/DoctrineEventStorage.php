<?php
namespace Neos\EventSourcing\EventStore\Storage\Doctrine;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Notice;
use Neos\Error\Messages\Result;
use Neos\Error\Messages\Warning;
use Neos\EventSourcing\Event\EventTypeResolver;
use Neos\EventSourcing\EventStore\EventStream;
use Neos\EventSourcing\EventStore\EventStreamFilterInterface;
use Neos\EventSourcing\EventStore\Exception\ConcurrencyException;
use Neos\EventSourcing\EventStore\ExpectedVersion;
use Neos\EventSourcing\EventStore\Storage\Doctrine\Factory\ConnectionFactory;
use Neos\EventSourcing\EventStore\Storage\Doctrine\Schema\EventStoreSchema;
use Neos\EventSourcing\EventStore\Storage\EventStorageInterface;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\EventSourcing\EventStore\StreamNameFilter;
use Neos\EventSourcing\EventStore\WritableEvents;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Utility\Now;

/**
 * Database event storage adapter
 */
class DoctrineEventStorage implements EventStorageInterface
{
    const DEFAULT_EVENT_TABLE_NAME = 'neos_eventsourcing_eventstore_events';

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
     * @var EventTypeResolver
     */
    protected $eventTypeResolver;

    /**
     * @Flow\Inject
     * @var Now
     */
    protected $now;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $eventTableName;

    /**
     * @var array
     */
    private $options;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
        $this->eventTableName = $options['eventTableName'] ?? self::DEFAULT_EVENT_TABLE_NAME;
    }

    /**
     * @return void
     */
    public function initializeObject()
    {
        $this->connection = $this->connectionFactory->create($this->options);
    }

    /**
     * @inheritdoc
     */
    public function load(EventStreamFilterInterface $filter): EventStream
    {
        $query = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->eventTableName)
            ->orderBy('sequencenumber', 'ASC');
        $this->applyEventStreamFilter($query, $filter);

        $streamIterator = new DoctrineStreamIterator($query);
        return new EventStream($streamIterator, $this->getStreamVersion($filter));
    }

    /**
     * @inheritdoc
     * @throws ConcurrencyException|\Exception
     */
    public function commit(string $streamName, WritableEvents $events, int $expectedVersion = ExpectedVersion::ANY): array
    {
        $this->connection->beginTransaction();
        $actualVersion = $this->getStreamVersion(new StreamNameFilter($streamName));
        $this->verifyExpectedVersion($actualVersion, $expectedVersion);

        $rawEvents = [];
        foreach ($events as $event) {
            $this->connection->insert(
                $this->eventTableName,
                [
                    'id' => $event->getIdentifier(),
                    'stream' => $streamName,
                    'version' => ++$actualVersion,
                    'type' => $event->getType(),
                    'payload' => json_encode($event->getData(), JSON_PRETTY_PRINT),
                    'metadata' => json_encode($event->getMetadata(), JSON_PRETTY_PRINT),
                    'recordedat' => $this->now
                ],
                [
                    'version' => \PDO::PARAM_INT,
                    'recordedat' => Type::DATETIME,
                ]
            );
            $sequenceNumber = $this->connection->lastInsertId();
            $rawEvents[] = new RawEvent($sequenceNumber, $event->getType(), $event->getData(), $event->getMetadata(), $actualVersion, $event->getIdentifier(), $this->now);
        }
        $this->connection->commit();
        return $rawEvents;
    }

    /**
     * @param EventStreamFilterInterface $filter
     * @return int
     */
    private function getStreamVersion(EventStreamFilterInterface $filter): int
    {
        $query = $this->connection->createQueryBuilder()
            ->select('MAX(version)')
            ->from($this->eventTableName);
        $this->applyEventStreamFilter($query, $filter);
        $version = $query->execute()->fetchColumn();
        return $version !== null ? (int)$version : -1;
    }

    /**
     * @param int $actualVersion
     * @param int $expectedVersion
     * @throws ConcurrencyException
     */
    private function verifyExpectedVersion(int $actualVersion, int $expectedVersion)
    {
        if ($expectedVersion === ExpectedVersion::ANY) {
            return;
        }
        if ($expectedVersion === $actualVersion) {
            return;
        }
        throw new ConcurrencyException(sprintf('Expected version: %s, actual version: %s', $this->renderExpectedVersion($expectedVersion), $this->renderExpectedVersion($actualVersion)), 1477143473);
    }

    /**
     * @param int $expectedVersion
     * @return string
     */
    private function renderExpectedVersion(int $expectedVersion)
    {
        if ($expectedVersion === ExpectedVersion::ANY) {
            return 'ANY (-2)';
        }
        if ($expectedVersion === ExpectedVersion::NO_STREAM) {
            return 'NO STREAM (-1)';
        }
        return (string)$expectedVersion;
    }

    /**
     * @param QueryBuilder $query
     * @param EventStreamFilterInterface $filter
     */
    private function applyEventStreamFilter(QueryBuilder $query, EventStreamFilterInterface $filter)
    {
        if ($filter->hasStreamName()) {
            $query->andWhere('stream = :streamName');
            $query->setParameter('streamName', $filter->getStreamName());
        } elseif ($filter->hasStreamNamePrefix()) {
            $query->andWhere('stream LIKE :streamNamePrefix');
            $query->setParameter('streamNamePrefix', $filter->getStreamNamePrefix() . '%');
        }
        if ($filter->hasEventTypes()) {
            $query->andWhere('type IN (:eventTypes)');
            $query->setParameter('eventTypes', $filter->getEventTypes(), Connection::PARAM_STR_ARRAY);
        }
        if ($filter->hasMinimumSequenceNumber()) {
            $query->andWhere('sequencenumber >= :minimumSequenceNumber');
            $query->setParameter('minimumSequenceNumber', $filter->getMinimumSequenceNumber());
        }
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        $result = new Result();
        try {
            $tableExists = $this->connection->getSchemaManager()->tablesExist([$this->eventTableName]);
        } catch (ConnectionException $exception) {
            $result->addError(new Error($exception->getMessage(), $exception->getCode(), [], 'Connection failed'));
            return $result;
        }
        $result->addNotice(new Notice($this->connection->getHost(), null, [], 'Host'));
        $result->addNotice(new Notice($this->connection->getPort(), null, [], 'Port'));
        $result->addNotice(new Notice($this->connection->getDatabase(), null, [], 'Database'));
        $result->addNotice(new Notice($this->connection->getDriver()->getName(), null, [], 'Driver'));
        $result->addNotice(new Notice($this->connection->getUsername(), null, [], 'Username'));
        if ($tableExists) {
            $result->addNotice(new Notice('%s (exists)', null, [$this->eventTableName], 'Table'));
        } else {
            $result->addWarning(new Warning('%s (missing)', null, [$this->eventTableName], 'Table'));
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function setup()
    {
        $result = new Result();
        try {
            $tableExists = $this->connection->getSchemaManager()->tablesExist([$this->eventTableName]);
        } catch (ConnectionException $exception) {
            $result->addError(new Error($exception->getMessage(), $exception->getCode(), [], 'Connection failed'));
            return $result;
        }
        if ($tableExists) {
            $result->addNotice(new Notice('Table "%s" (already exists)', null, [$this->eventTableName], 'Skipping'));
            return $result;
        }
        $result->addNotice(new Notice('Creating database table "%s" in database "%s" on host %s....', null, [$this->eventTableName, $this->connection->getDatabase(), $this->connection->getHost()]));

        $schema = $this->connection->getSchemaManager()->createSchema();
        $toSchema = clone $schema;

        EventStoreSchema::createStream($toSchema, $this->eventTableName);

        $this->connection->beginTransaction();
        $statements = $schema->getMigrateToSql($toSchema, $this->connection->getDatabasePlatform());
        foreach ($statements as $statement) {
            $result->addNotice(new Notice('<info>++</info> %s', null, [$statement]));
            $this->connection->exec($statement);
        }
        $this->connection->commit();
        return $result;
    }
}
