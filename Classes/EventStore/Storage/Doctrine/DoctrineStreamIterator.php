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

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use Neos\Cqrs\EventStore\RawEvent;

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
     * @var bool
     */
    private $rewound = false;

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = clone $queryBuilder;
        $this->queryBuilder->setMaxResults(self::BATCH_SIZE);
        $this->rewind();
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
            $currentEventData['id'],
            $currentEventData['type'],
            $payload,
            $metadata,
            (int)$currentEventData['version'],
            $recordedAt
        );
    }

    /**
     * @return void
     */
    public function next()
    {
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
        return $this->innerIterator->current()['id'];
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
        if ($this->rewound) {
            return;
        }
        $this->fetchBatch();
        $this->rewound = true;
    }

    /**
     * Fetches a batch of maximum BATCH_SIZE records
     *
     * @return void
     */
    private function fetchBatch()
    {
        $this->queryBuilder->setFirstResult($this->currentOffset);
        $rawResult = $this->queryBuilder->execute()->fetchAll();
        $this->currentOffset += count($rawResult);
        $this->innerIterator = new \ArrayIterator($rawResult);
    }
}
