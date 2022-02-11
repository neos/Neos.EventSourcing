<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventStore\Storage\InMemory;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use ArrayIterator;
use Neos\EventSourcing\EventStore\EventStreamIteratorInterface;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\EventSourcing\EventStore\StreamName;

/**
 * Stream Iterator for an in-memory based EventStore – intended for testing
 */
final class InMemoryStreamIterator implements EventStreamIteratorInterface
{
    /**
     * @var int
     */
    private $currentOffset = 0;

    /**
     * @var ArrayIterator
     */
    private $innerIterator;

    /**
     * @param array $eventRecords
     */
    public function __construct(array $eventRecords)
    {
        $this->innerIterator = new ArrayIterator($eventRecords);
    }

    /**
     * @return RawEvent
     * @throws \JsonException
     */
    public function current(): RawEvent
    {
        $currentEventData = $this->innerIterator->current();
        $payload = json_decode($currentEventData['payload'], true, 512, JSON_THROW_ON_ERROR);
        $metadata = json_decode($currentEventData['metadata'], true, 512, JSON_THROW_ON_ERROR);
        try {
            $recordedAt = new \DateTimeImmutable($currentEventData['recordedat']);
        } catch (\Exception $exception) {
            throw new \RuntimeException(sprintf('Could not parse recordedat timestamp "%s" as date.', $currentEventData['recordedat']), 1597843669, $exception);
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
    }

    /**
     * @return bool|int|null
     */
    public function key(): bool|int|null
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
        $this->innerIterator->rewind();
        $this->currentOffset = 0;
    }
}
