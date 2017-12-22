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

use Neos\Error\Messages\Result;
use Neos\EventSourcing\EventStore\EventStream;
use Neos\EventSourcing\EventStore\EventStreamFilterInterface;
use Neos\EventSourcing\EventStore\ExpectedVersion;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\EventSourcing\EventStore\WritableEvents;

/**
 * Contract for Event Storage adapters
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

    public function getStreamVersion(EventStreamFilterInterface $filter): int;

    /**
     * Retrieves the (connection) status of the storage adapter
     *
     * If the result contains no errors, the status is considered valid
     * The result may contain Notices, Warnings and Errors
     *
     * @return Result
     */
    public function getStatus();

    /**
     * Sets up the configured storage adapter (i.e. creates required database tables) and validates the configuration
     *
     * If the result contains no errors, the setup is considered successful
     * The result may contain Notices, Warnings and Errors
     *
     * @return Result
     */
    public function setup();
}
