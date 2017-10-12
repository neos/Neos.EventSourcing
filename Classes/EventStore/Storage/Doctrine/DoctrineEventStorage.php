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
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
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
        return new EventStream($streamIterator);
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
            $metadata = $event->getMetadata();
            $this->connection->insert(
                $this->eventTableName,
                [
                    'id' => $event->getIdentifier(),
                    'stream' => $streamName,
                    'version' => ++$actualVersion,
                    'type' => $event->getType(),
                    'payload' => json_encode($event->getData(), JSON_PRETTY_PRINT),
                    'metadata' => json_encode($metadata, JSON_PRETTY_PRINT),
                    'correlationid' => $metadata['correlationId'] ?? null,
                    'causationid' => $metadata['causationId'] ?? null,
                    'recordedat' => $this->now
                ],
                [
                    'version' => \PDO::PARAM_INT,
                    'recordedat' => Type::DATETIME,
                ]
            );
            $sequenceNumber = $this->connection->lastInsertId();
            $rawEvents[] = new RawEvent($sequenceNumber, $event->getType(), $event->getData(), $metadata, $actualVersion, $event->getIdentifier(), $this->now);
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
        $filterValues = $filter->getFilterValues();
        if (array_key_exists(EventStreamFilterInterface::FILTER_STREAM_NAME, $filterValues)) {
            $query->andWhere('stream = :streamName');
            $query->setParameter('streamName', $filterValues[EventStreamFilterInterface::FILTER_STREAM_NAME]);
        } elseif (array_key_exists(EventStreamFilterInterface::FILTER_STREAM_NAME_PREFIX, $filterValues)) {
            $query->andWhere('stream LIKE :streamNamePrefix');
            $query->setParameter('streamNamePrefix', $filterValues[EventStreamFilterInterface::FILTER_STREAM_NAME_PREFIX] . '%');
        }
        if (array_key_exists(EventStreamFilterInterface::FILTER_EVENT_TYPES, $filterValues)) {
            $query->andWhere('type IN (:eventTypes)');
            $query->setParameter('eventTypes', $filterValues[EventStreamFilterInterface::FILTER_EVENT_TYPES], Connection::PARAM_STR_ARRAY);
        }
        if (array_key_exists(EventStreamFilterInterface::FILTER_MINIMUM_SEQUENCE_NUMBER, $filterValues)) {
            $query->andWhere('sequencenumber >= :minimumSequenceNumber');
            $query->setParameter('minimumSequenceNumber', $filterValues[EventStreamFilterInterface::FILTER_MINIMUM_SEQUENCE_NUMBER]);
        }
        if (array_key_exists(EventStreamFilterInterface::FILTER_CORRELATION_ID, $filterValues)) {
            $query->andWhere('correlationid = :correlationId');
            $query->setParameter('correlationId', $filterValues[EventStreamFilterInterface::FILTER_CORRELATION_ID]);
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
        $result->addNotice(new Notice((string)$this->connection->getHost(), null, [], 'Host'));
        $result->addNotice(new Notice((string)$this->connection->getPort(), null, [], 'Port'));
        $result->addNotice(new Notice((string)$this->connection->getDatabase(), null, [], 'Database'));
        $result->addNotice(new Notice((string)$this->connection->getDriver()->getName(), null, [], 'Driver'));
        $result->addNotice(new Notice((string)$this->connection->getUsername(), null, [], 'Username'));
        if ($tableExists) {
            $result->addNotice(new Notice('%s (exists)', null, [$this->eventTableName], 'Table'));

            $fromSchema = $this->connection->getSchemaManager()->createSchema();
            $schemaDiff = (new Comparator())->compare($fromSchema, $this->createEventStoreSchema());
            $statements = $schemaDiff->toSaveSql($this->connection->getDatabasePlatform());
            if ($statements !== []) {
                $result->addWarning(new Warning('The schama of table %s is not up-to-date', null, [$this->eventTableName], 'Table schema'));
                foreach ($statements as $statement) {
                    $result->addWarning(new Warning($statement, null, [], 'Required statement'));
                }
            }

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
            $result->addNotice(new Notice('Table "%s" (already exists)', null, [$this->eventTableName]));
        } else {
            $result->addNotice(new Notice('Creating database table "%s" in database "%s" on host %s....', null, [$this->eventTableName, $this->connection->getDatabase(), $this->connection->getHost()]));
        }

        $fromSchema = $this->connection->getSchemaManager()->createSchema();
        $schemaDiff = (new Comparator())->compare($fromSchema, $this->createEventStoreSchema());

        $statements = $schemaDiff->toSaveSql($this->connection->getDatabasePlatform());
        if ($statements === []) {
            $result->addNotice(new Notice('Table schema is up to date, no migration required'));
            return $result;
        }
        $this->connection->beginTransaction();
        foreach ($statements as $statement) {
            $result->addNotice(new Notice('<info>++</info> %s', null, [$statement]));
            $this->connection->exec($statement);
        }
        $this->connection->commit();
        return $result;
    }

    /**
     * Creates the Doctrine schema to be compared with the current db schema for migration
     *
     * @return Schema
     */
    private function createEventStoreSchema()
    {
        $schema = new Schema();
        $table = $schema->createTable($this->eventTableName);

        // The monotonic sequence number
        $table->addColumn('sequencenumber', Type::INTEGER, ['autoincrement' => true]);
        // The stream name, usually in the format "<BoundedContext>:<StreamName>"
        $table->addColumn('stream', Type::STRING, ['length' => 255]);
        // Version of the event in the respective stream
        $table->addColumn('version', Type::BIGINT, ['unsigned' => true]);
        // The event type in the format "<BoundedContext>:<EventType>"
        $table->addColumn('type', Type::STRING, ['length' => 255]);
        // The event payload as JSON
        $table->addColumn('payload', Type::TEXT);
        // The event metadata as JSON
        $table->addColumn('metadata', Type::TEXT);
        // The unique event id, usually a UUID
        $table->addColumn('id', Type::STRING, ['length' => 255]);
        // An optional correlation id, usually a UUID
        $table->addColumn('correlationid', Type::STRING, ['length' => 255, 'notnull' => false]);
        // An optional causation id, usually a UUID
        $table->addColumn('causationid', Type::STRING, ['length' => 255, 'notnull' => false]);
        // Timestamp of the the event publishing
        $table->addColumn('recordedat', Type::DATETIME);

        $table->setPrimaryKey(['sequencenumber']);
        $table->addUniqueIndex(['id'], 'id_uniq');
        $table->addUniqueIndex(['stream', 'version'], 'stream_version_uniq');

        return $schema;
    }
}
