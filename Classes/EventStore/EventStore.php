<?php
namespace Neos\EventSourcing\EventStore;

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
use Neos\EventSourcing\EventBus\EventBus;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcing\Event\Decorator\DomainEventWithMetadataInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\Event\EventTypeResolver;
use Neos\EventSourcing\EventStore\Exception\EventStreamNotFoundException;
use Neos\EventSourcing\EventStore\Storage\EventStorageInterface;
use Neos\Flow\Utility\Algorithms;

/**
 * Main API to store and fetch events.
 *
 * NOTE: Do not instantiate this class directly but use the EventStoreManager
 */
final class EventStore
{
    /**
     * @var EventStorageInterface
     */
    private $storage;

    /**
     * TODO replace
     *
     * @Flow\Inject
     * @var EventTypeResolver
     */
    protected $eventTypeResolver;

    /**
     * TODO replace
     *
     * @Flow\Inject
     * @var EventNormalizer
     */
    protected $eventNormalizer;

    /**
     * @Flow\Inject
     * @var EventBus
     */
    protected $eventBus;

    /**
     * @internal Do not instantiate this class directly but use the EventStoreManager
     * @param EventStorageInterface $storage
     */
    public function __construct(EventStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function load(StreamName $streamName, string $eventIdentifier = null): EventStream
    {
        $eventStream = $this->storage->load($streamName, $eventIdentifier);
        if (!$eventStream->valid()) {
            throw new EventStreamNotFoundException(sprintf('The event stream "%s" does not appear to be valid.', $streamName), 1477497156);
        }
        return $eventStream;
    }

    /**
     * @param StreamName $streamName
     * @param DomainEvents $events
     * @param int $expectedVersion
     */
    public function commit(StreamName $streamName, DomainEvents $events, int $expectedVersion = ExpectedVersion::ANY): void
    {
        if ($events->isEmpty()) {
            return;
        }
        $convertedEvents = [];
        foreach ($events as $event) {
            $metadata = [];
            if ($event instanceof DomainEventWithMetadataInterface) {
                $metadata = $event->getMetadata();
                $event = $event->getEvent();
            }
            $type = $this->eventTypeResolver->getEventType($event);
            $data = $this->eventNormalizer->normalize($event);
            $eventIdentifier = Algorithms::generateUUID();
            $convertedEvents[] = new WritableEvent($eventIdentifier, $type, $data, $metadata);
        }

        $this->storage->commit($streamName, WritableEvents::fromArray($convertedEvents), $expectedVersion);
        foreach ($events as $event) {
            $this->eventBus->dispatch($event);
        }
    }

    /**
     * Returns the (connection) status of this Event Store, @see EventStorageInterface::getStatus()
     *
     * @return Result
     */
    public function getStatus(): Result
    {
        return $this->storage->getStatus();
    }

    /**
     * Sets up this Event Store and returns a status, @see EventStorageInterface::setup()
     *
     * @return Result
     */
    public function setup(): Result
    {
        return $this->storage->setup();
    }
}
