<?php
namespace Neos\Cqrs\EventStore;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\EventStore\Exception\EventStreamNotFoundException;
use Neos\Cqrs\EventStore\Storage\EventStorageInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class EventStore
{
    /**
     * @var EventStorageInterface
     */
    private $storage;

    public function __construct(EventStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param EventStreamFilterInterface $filter
     * @return EventStream Can be empty stream
     * @throws EventStreamNotFoundException
     * @todo improve exception message, log the current filter type and configuration
     */
    public function get(EventStreamFilterInterface $filter): EventStream
    {
        $eventStream = $this->storage->load($filter);
        if (!$eventStream->valid()) {
            throw new EventStreamNotFoundException(sprintf('The event stream "%s" does not appear to be valid.', $filter->getStreamName()), 1477497156);
        }
        return $eventStream;
    }

    /**
     * @param string $streamName
     * @param WritableEvents $events
     * @param int $expectedVersion
     * @return RawEvent[]
     */
    public function commit(string $streamName, WritableEvents $events, int $expectedVersion = ExpectedVersion::ANY): array
    {
        return $this->storage->commit($streamName, $events, $expectedVersion);
    }
}
