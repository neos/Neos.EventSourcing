<?php
namespace Flowpack\Cqrs\EventStore;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Domain\Uuid;
use Flowpack\Cqrs\Event\EventInterface;
use Flowpack\Cqrs\EventStore\EventSerializer\EventSerializerInterface;
use Flowpack\Cqrs\EventStore\Exception\ConcurrencyException;
use Flowpack\Cqrs\EventStore\Exception\EventStreamNotFoundException;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventStore
 */
class EventStore implements EventStoreInterface
{
    /** @var EventStorageInterface */
    protected $storage;

    /** @var EventSerializerInterface */
    protected $serializer;

    /**
     * EventStoreWrapper constructor
     * @param EventStorageInterface $storage
     * @param EventSerializerInterface $serializer
     */
    public function __construct(EventStorageInterface $storage, EventSerializerInterface $serializer)
    {
        $this->storage = $storage;
        $this->serializer = $serializer;
    }

    /**
     * Get events for AR
     * @param  Uuid $aggregateRootId
     * @return EventStream Can be empty stream
     * @throws EventStreamNotFoundException
     */
    public function get(Uuid $aggregateRootId)
    {
        /** @var EventStreamData $streamData */
        $streamData = $this->storage->load($aggregateRootId);
        
        if (!$streamData || (!$streamData instanceof EventStreamData)) {
            throw new EventStreamNotFoundException();
        }

        $events = [];

        foreach ($streamData->data as $eventData) {
            $events[] = $this->serializer->deserialize($eventData);
        }

        return new EventStream(
            new Uuid($streamData->id),
            $streamData->name,
            $events,
            $streamData->version
        );
    }

    /**
     * Persist new AR events
     * @param  EventStream $stream
     * @throws ConcurrencyException
     */
    public function commit(EventStream $stream)
    {
        $newEvents = $stream->getNewEvents();

        $newEventsQuantity = count($newEvents);

        if (!$newEventsQuantity) {
            return;
        }

        $aggregateRootId = $stream->getAggregateId();
        $aggregateName = $stream->getAggregateName();
        $currentVersion = $stream->getVersion();
        $newVersion = $currentVersion + $newEventsQuantity;

        $eventData = [];

        /** @var EventInterface $event */
        foreach ($newEvents as $event) {
            $eventData[] = $this->serializer->serialize($event);
        }

        $currentStoredVersion = $this->storage->getCurrentVersion($aggregateRootId);

        if ($currentVersion !== $currentStoredVersion) {
            throw new ConcurrencyException('Aggregate root versions mismatch');
        }

        $this->storage->write($aggregateRootId, $aggregateName, $eventData, $newVersion);

        $stream->markAllApplied($newVersion);

        return;
    }
}
