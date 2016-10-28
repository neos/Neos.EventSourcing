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
     * @param EventStreamFilter $filter
     * @return EventStream Can be empty stream
     * @throws EventStreamNotFoundException
     */
    public function get(EventStreamFilter $filter): EventStream
    {
        $eventStream = $this->storage->load($filter);
        if (!$eventStream->valid()) {
            throw new EventStreamNotFoundException(sprintf('The event stream "%s" does not exist/is empty', $filter), 1477497156);
        }
        return $eventStream;
    }

    public function commit(string $streamName, WritableEvents $events, int $expectedVersion = ExpectedVersion::ANY)
    {
        $this->storage->commit($streamName, $events, $expectedVersion);
    }
}
