<?php
namespace Neos\EventSourcing\EventStore\Storage;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\EventStore\EventStream;
use Neos\EventSourcing\EventStore\EventStreamFilterInterface;
use Neos\EventSourcing\EventStore\ExpectedVersion;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\EventSourcing\EventStore\WritableEvents;

/**
 * EventStorageInterface
 */
interface EventStorageInterface
{
    /**
     * @param EventStreamFilterInterface $filter
     * @return EventStream
     */
    public function load(EventStreamFilterInterface $filter): EventStream;

    /**
     * @param string $streamName
     * @param WritableEvents $events
     * @param int $expectedVersion
     * @return RawEvent[]
     */
    public function commit(string $streamName, WritableEvents $events, int $expectedVersion = ExpectedVersion::ANY): array;
}
