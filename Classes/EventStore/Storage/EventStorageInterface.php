<?php
declare(strict_types=1);
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
use Neos\EventSourcing\EventStore\EventStreamIteratorInterface;
use Neos\EventSourcing\EventStore\Exception\ConcurrencyException;
use Neos\EventSourcing\EventStore\ExpectedVersion;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\EventSourcing\EventStore\WritableEvents;

/**
 * Contract for Event Storage adapters
 */
interface EventStorageInterface
{
    /**
     * @param StreamName $filter
     * @param int $minimumSequenceNumber
     * @return EventStreamIteratorInterface
     */
    public function load(StreamName $filter, int $minimumSequenceNumber = 0): EventStreamIteratorInterface;

    /**
     * @param StreamName $streamName
     * @param WritableEvents $events
     * @param int $expectedVersion
     * @return void
     * @throws ConcurrencyException in case the $expectedVersion is not the highest version in the stream
     */
    public function commit(StreamName $streamName, WritableEvents $events, int $expectedVersion = ExpectedVersion::ANY): void;

    /**
     * Retrieves the (connection) status of the storage adapter
     *
     * If the result contains no errors, the status is considered valid
     * The result may contain Notices, Warnings and Errors
     *
     * @return Result
     */
    public function getStatus(): Result;

    /**
     * Sets up the configured storage adapter (i.e. creates required database tables) and validates the configuration
     *
     * If the result contains no errors, the setup is considered successful
     * The result may contain Notices, Warnings and Errors
     *
     * @return Result
     */
    public function setup(): Result;
}
