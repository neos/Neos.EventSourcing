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

use Doctrine\DBAL\Query\QueryBuilder;
use Neos\EventSourcing\EventStore\RawEvent;

/**
 * Stream Iterator for the doctrine based EventStore
 */
final class DoctrineStreamIterator implements \Iterator
{

    /**
     * The number of records to fetch per batch
     *
     * @var int
     */
    const BATCH_SIZE = 100;

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
    public function current()
    {
        $currentEventData = $this->innerIterator->current();
        $payload = json_decode($currentEventData['payload'], true);
        $metadata = json_decode($currentEventData['metadata'], true);
        $recordedAt = new \DateTimeImmutable($currentEventData['recordedat']);
        return new RawEvent(
            $currentEventData['sequencenumber'],
            $currentEventData['type'],
            $payload,
            $metadata,
            $currentEventData['stream'],
            (int)$currentEventData['version'],
            $currentEventData['id'],
            $recordedAt
        );
    }

    /**
     * @return void
     */
    public function next()
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
    public function key()
    {
        return $this->innerIterator->valid() ? $this->innerIterator->current()['sequencenumber'] : null;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->innerIterator->valid();
    }

    /**
     * @return void
     */
    public function rewind()
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
    private function fetchBatch()
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
    private function reconnectDatabaseConnection()
    {
        if ($this->queryBuilder->getConnection()->ping() === false) {
            $this->queryBuilder->getConnection()->close();
            $this->queryBuilder->getConnection()->connect();
        }
    }
}
