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

    public function get(string $streamName): EventStream
    {
        return $this->storage->load($streamName);
    }

    public function commit(string $streamName, WritableToStreamInterface $events, int $expectedVersion = ExpectedVersion::ANY)
    {
        $this->storage->commit($streamName, $events, $expectedVersion);
    }
}
