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

use Doctrine\DBAL\Query\QueryBuilder;
use Neos\EventSourcing\EventStore\EventStreamIteratorInterface;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\EventSourcing\EventStore\StreamName;

/**
 * Stream Iterator for the doctrine based EventStore
 */
final class DoctrineStreamIterator implements EventStreamIteratorInterface
{

    /**
     * The number of records to fetch per batch
     *
     * @var int
     */
    private const BATCH_SIZE = 100;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var int
     */
    private $currentOffset = 0;

    /**
     * @var \ArrayIterator
     */
    private $innerIterator;

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = clone $queryBuilder;
        $this->queryBuilder->setMaxResults(self::BATCH_SIZE);
        $this->queryBuilder->andWhere('sequencenumber > :sequenceNumberOffset');
        $this->fetchBatch();
    }

    /**
     * @return RawEvent
     */
    public function current(): RawEvent
    {
        $currentEventData = $this->innerIterator->current();
        $payload = json_decode($currentEventData['payload'], true);
        $metadata = json_decode($currentEventData['metadata'], true);
        try {
            $recordedAt = new \DateTimeImmutable($currentEventData['recordedat']);
        } catch (\Exception $exception) {
            throw new \RuntimeException(sprintf('Could not parse recordedat timestamp "%s" as date.', $currentEventData['recordedat']), 1544211618, $exception);
        }
        return new RawEvent(
            (int)$currentEventData['sequencenumber'],
            $currentEventData['type'],
            $payload,
            $metadata,
            StreamName::fromString($currentEventData['stream']),
            (int)$currentEventData['version'],
            $currentEventData['id'],
            $recordedAt
        );
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $this->currentOffset = $this->innerIterator->current()['sequencenumber'];
        $this->innerIterator->next();
        if ($this->innerIterator->valid()) {
            return;
        }
        $this->fetchBatch();
    }

    /**
     * @return bool|int
     */
    public function key(): mixed
    {
        return $this->innerIterator->valid() ? $this->innerIterator->current()['sequencenumber'] : null;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->innerIterator->valid();
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        if ($this->currentOffset === 0) {
            return;
        }
        $this->currentOffset = 0;
        $this->fetchBatch();
    }

    /**
     * Fetches a batch of maximum BATCH_SIZE records
     *
     * @return void
     */
    private function fetchBatch(): void
    {
        // we deliberately don't use "setFirstResult" here, as this translates to an OFFSET query. For resolving
        // an OFFSET query, the DB needs to scan the result-set from the beginning (which is slow as hell).
        $this->queryBuilder->setParameter('sequenceNumberOffset', $this->currentOffset);
        $this->reconnectDatabaseConnection();
        $rawResult = $this->queryBuilder->execute()->fetchAll();
        $this->innerIterator = new \ArrayIterator($rawResult);
    }

    /**
     * Reconnects the database connection associated with this storage, if it doesn't respond to a ping
     *
     * @see \Neos\Flow\Persistence\Doctrine\PersistenceManager::persistAll()
     * @return void
     */
    private function reconnectDatabaseConnection(): void
    {
        try {
            $this->queryBuilder->getConnection()->fetchOne('SELECT 1');
        } catch (\Exception $e) {
            $this->queryBuilder->getConnection()->close();
            $this->queryBuilder->getConnection()->connect();
        }
    }
}
