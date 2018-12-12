<?php
declare(strict_types=1);
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
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Notice;
use Neos\Error\Messages\Result;
use Neos\Error\Messages\Warning;
use Neos\EventSourcing\EventStore\EventStream;
use Neos\EventSourcing\EventStore\Exception\ConcurrencyException;
use Neos\EventSourcing\EventStore\ExpectedVersion;
use Neos\EventSourcing\EventStore\Storage\CorrelationIdAwareEventStorageInterface;
use Neos\EventSourcing\EventStore\Storage\Doctrine\Factory\ConnectionFactory;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\EventSourcing\EventStore\WritableEvents;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Now;

/**
 * Database event storage adapter
 */
class DoctrineEventStorage implements CorrelationIdAwareEventStorageInterface
{
    const DEFAULT_EVENT_TABLE_NAME = 'neos_eventsourcing_eventstore_events';

    /**
     * @var ConnectionFactory
     * @Flow\Inject
     */
    protected $connectionFactory;

    /**
     * @Flow\Inject(lazy=false)
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
     * @throws DBALException
     */
    public function initializeObject(): void
    {
        $this->connection = $this->connectionFactory->create($this->options);
    }

    /**
     * @inheritdoc
     */
    public function load(StreamName $streamName, string $eventIdentifier = null): EventStream
    {
        $this->reconnectDatabaseConnection();
        $query = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->eventTableName)
            ->orderBy('sequencenumber', 'ASC');

        if ($eventIdentifier !== null) {
            $eventSequenceNumber = $this->getEventSequenceNumber($eventIdentifier);
            $query->andWhere('sequenceNumber > :sequenceNumber');
            $query->setParameter('sequenceNumber', $eventSequenceNumber);
        }

        if (!$streamName->isVirtualStream()) {
            $query->andWhere('stream = :streamName');
            $query->setParameter('streamName', (string)$streamName);
        } elseif (!$streamName->isAll()) {
            $query->andWhere('stream LIKE :streamNamePrefix');
            $query->setParameter('streamNamePrefix', $streamName->getCategoryName() . '%');
        }

        $streamIterator = new DoctrineStreamIterator($query);
        return new EventStream($streamName, $streamIterator);
    }

    public function loadByCorrelationId(string $correlationId): EventStream
    {
        $this->reconnectDatabaseConnection();
        $query = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->eventTableName)
            ->where('correlationidentifier = :correlationId')
            ->orderBy('sequencenumber', 'ASC')
            ->setParameter('correlationId', $correlationId);

        $streamIterator = new DoctrineStreamIterator($query);
        return new EventStream(StreamName::forCorrelationId($correlationId), $streamIterator);
    }

    /**
     * @inheritdoc
     * @throws DBALException | ConcurrencyException
     */
    public function commit(StreamName $streamName, WritableEvents $events, int $expectedVersion = ExpectedVersion::ANY): void
    {
        if ($streamName->isVirtualStream()) {
            throw new \InvalidArgumentException(sprintf('Can\'t commit to virtual stream "%s"', $streamName), 1540632984);
        }
        $this->reconnectDatabaseConnection();
        $this->connection->beginTransaction();
        try {
            $actualVersion = $this->getStreamVersion($streamName);
            $this->verifyExpectedVersion($actualVersion, $expectedVersion);

            foreach ($events as $event) {
                $metadata = $event->getMetadata();
                $this->connection->insert(
                    $this->eventTableName,
                    [
                        'id' => $event->getIdentifier(),
                        'stream' => (string)$streamName,
                        'version' => ++$actualVersion,
                        'type' => $event->getType(),
                        'payload' => json_encode($event->getData(), JSON_PRETTY_PRINT),
                        'metadata' => json_encode($metadata, JSON_PRETTY_PRINT),
                        'correlationidentifier' => $metadata['correlationIdentifier'] ?? null,
                        'causationidentifier' => $metadata['causationIdentifier'] ?? null,
                        'recordedat' => $this->now
                    ],
                    [
                        'version' => \PDO::PARAM_INT,
                        'recordedat' => Type::DATETIME,
                    ]
                );
            }

            $this->connection->commit();
        } catch (DBALException $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    /**
     * @param StreamName $streamName
     * @return int
     */
    private function getStreamVersion(StreamName $streamName): int
    {
        $version = $this->connection->createQueryBuilder()
            ->select('MAX(version)')
            ->from($this->eventTableName)
            ->where('stream = :streamName')
            ->setParameter('streamName', (string)$streamName)
            ->execute()
            ->fetchColumn();
        return $version !== null ? (int)$version : -1;
    }

    /**
     * @param string $eventIdentifier
     * @return int
     */
    private function getEventSequenceNumber(string $eventIdentifier): int
    {
        $sequenceNumber = $this->connection->createQueryBuilder()
            ->select('sequenceNumber')
            ->from($this->eventTableName)
            ->where('id = :eventIdentifier')
            ->setParameter('eventIdentifier', $eventIdentifier)
            ->execute()
            ->fetchColumn();
        if ($sequenceNumber === null) {
            throw new \RuntimeException(sprintf('Event with id "%s" not found', $eventIdentifier), 1540636494);
        }
        return (int)$sequenceNumber;
    }

    /**
     * @param int $actualVersion
     * @param int $expectedVersion
     * @throws ConcurrencyException
     */
    private function verifyExpectedVersion(int $actualVersion, int $expectedVersion): void
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
    private function renderExpectedVersion(int $expectedVersion): string
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
     * @inheritdoc
     * @throws DBALException
     */
    public function getStatus(): Result
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
                $result->addWarning(new Warning('The schema of table %s is not up-to-date', null, [$this->eventTableName], 'Table schema'));
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
     * @throws DBALException
     * @throws \Exception
     */
    public function setup(): Result
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
        try {
            foreach ($statements as $statement) {
                $result->addNotice(new Notice('<info>++</info> %s', null, [$statement]));
                $this->connection->exec($statement);
            }
            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
        return $result;
    }

    /**
     * Creates the Doctrine schema to be compared with the current db schema for migration
     *
     * @return Schema
     */
    private function createEventStoreSchema(): Schema
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
        $table->addColumn('correlationidentifier', Type::STRING, ['length' => 255, 'notnull' => false]);
        // An optional causation id, usually a UUID
        $table->addColumn('causationidentifier', Type::STRING, ['length' => 255, 'notnull' => false]);
        // Timestamp of the the event publishing
        $table->addColumn('recordedat', Type::DATETIME);

        $table->setPrimaryKey(['sequencenumber']);
        $table->addUniqueIndex(['id'], 'id_uniq');
        $table->addUniqueIndex(['stream', 'version'], 'stream_version_uniq');

        return $schema;
    }

    /**
     * Reconnects the database connection associated with this storage, if it doesn't respond to a ping
     *
     * @see \Neos\Flow\Persistence\Doctrine\PersistenceManager::persistAll()
     * @return void
     */
    private function reconnectDatabaseConnection(): void
    {
        if ($this->connection->ping() === false) {
            $this->connection->close();
            $this->connection->connect();
        }
    }
}
