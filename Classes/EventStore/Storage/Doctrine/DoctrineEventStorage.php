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
use Doctrine\DBAL\Types\Type;
use Neos\Cqrs\Event\EventTypeResolver;
use Neos\Cqrs\EventStore\EventStoreCommit;
use Neos\Cqrs\EventStore\EventStream;
use Neos\Cqrs\EventStore\EventStreamFilterInterface;
use Neos\Cqrs\EventStore\Exception\ConcurrencyException;
use Neos\Cqrs\EventStore\ExpectedVersion;
use Neos\Cqrs\EventStore\Storage\Doctrine\Factory\ConnectionFactory;
use Neos\Cqrs\EventStore\Storage\EventStorageInterface;
use Neos\Cqrs\EventStore\StreamNameFilter;
use Neos\Cqrs\EventStore\WritableEvents;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Utility\Now;

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
            ->orderBy('id', 'ASC');
        $this->applyEventStreamFilter($query, $filter);

        $streamIterator = new DoctrineStreamIterator($query->execute());
        return new EventStream($streamIterator, $this->getStreamVersion($filter));
    }

    /**
     * @param EventStoreCommit $commit
     * @return void
     * @throws ConcurrencyException|\Exception
     */
    public function commit(EventStoreCommit $commit)
    {
        $this->connection->beginTransaction();
        $actualVersion = $this->getStreamVersion(new StreamNameFilter($commit->getStreamName()));
        $this->verifyExpectedVersion($actualVersion, $commit->getExpectedVersion());

        foreach ($commit->getEvents() as $event) {
            $this->connection->insert(
                $this->connectionFactory->getStreamTableName(),
                [
                    'stream' => $commit->getStreamName(),
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
        }
        $this->connection->commit();
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
    }
}
