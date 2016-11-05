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
use TYPO3\Flow\Annotations as Flow;

/**
 * EventStoreCommit
 */
class EventStoreCommit
{
    /**
     * @var string
     */
    protected $streamName;

    /**
     * @var WritableEvents
     */
    protected $events;

    /**
     * @var int
     */
    protected $expectedVersion;

    /**
     * @param string $streamName
     * @param WritableEvents $events
     * @param int $expectedVersion
     */
    public function __construct(string $streamName, WritableEvents $events, int $expectedVersion = ExpectedVersion::ANY)
    {
        $this->streamName = $streamName;
        $this->events = $events;
        $this->expectedVersion = $expectedVersion;
    }

    /**
     * @return string
     */
    public function getStreamName(): string
    {
        return $this->streamName;
    }

    /**
     * @return WritableEvents
     */
    public function getEvents(): WritableEvents
    {
        return $this->events;
    }

    /**
     * @return int
     */
    public function getExpectedVersion(): int
    {
        return $this->expectedVersion;
    }
}
