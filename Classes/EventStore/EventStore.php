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

use Neos\Cqrs\EventStore\Exception\ConcurrencyException;
use Neos\Cqrs\EventStore\Exception\EventStreamNotFoundException;
use Neos\Cqrs\EventStore\Storage\EventStorageInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;

/**
 * EventStore
 */
class EventStore
{
    /**
     * @var EventStorageInterface
     * @Flow\Inject
     */
    protected $storage;

    /**
     * @var SystemLoggerInterface
     * @Flow\Inject
     */
    protected $logger;

    /**
     * @param EventStreamFilter $filter
     * @return EventStream Can be empty stream
     * @throws EventStreamNotFoundException
     */
    public function get(EventStreamFilter $filter): EventStream
    {
        $streamData = $this->storage->load($filter);

        if (!$streamData || (!$streamData instanceof EventStreamData)) {
            throw new EventStreamNotFoundException();
        }

        return new EventStream(
            $streamData->getData(),
            $streamData->getVersion()
        );
    }

    /**
     * @param string $streamName
     * @param EventStream $stream
     * @return int committed version number
     * @param \Closure $callback
     * @throws ConcurrencyException
     */
    public function commit(string $streamName, EventStream $stream, \Closure $callback = null) :int
    {
        $newEvents = $stream->getNewEvents();

        if ($newEvents === []) {
            return $this->storage->getCurrentVersion($streamName);
        }

        $currentVersion = $stream->getVersion();
        $expectedVersion = $currentVersion + count($newEvents);
        $this->storage->commit($streamName, $newEvents, $expectedVersion, $callback);
        $stream->markAllApplied($expectedVersion);

        return $expectedVersion;
    }
}
