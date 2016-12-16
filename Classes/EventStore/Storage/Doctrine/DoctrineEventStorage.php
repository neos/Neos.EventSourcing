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
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
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

    protected function initializeObject()
    {
        $this->connection = $this->connectionFactory->get();
    }

    public function load(EventStreamFilterInterface $filter): EventStream
    {
        $query = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->connectionFactory->getStreamTableName())
            ->orderBy('sequencenumber', 'ASC');
        $this->applyEventStreamFilter($query, $filter);

        $streamIterator = new DoctrineStreamIterator($query);
        return new EventStream($streamIterator, $this->getStreamVersion($filter));
    }

    /**
     * @param string $streamName
     * @param WritableEvents $events
     * @param int $expectedVersion
     * @return RawEvent[]
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
                $this->connectionFactory->getStreamTableName(),
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
            ->from($this->connectionFactory->getStreamTableName());
        $this->applyEventStreamFilter($query, $filter);
        $version = $query->execute()->fetchColumn();
        return $version !== null ? (int)$version : -1;
    }

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
}
