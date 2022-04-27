<?php
declare(strict_types=1);
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
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventPublisher\EventPublisherInterface;
use Neos\EventSourcing\EventStore\Exception\ConcurrencyException;
use Neos\EventSourcing\EventStore\Storage\EventStorageInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;

/**
 * Main API to store and fetch events.
 *
 * NOTE: Do not instantiate this class directly but use the EventStoreFactory (or inject an instance which internally uses the factory)
 */
final class EventStore
{
    /**
     * @var EventStorageInterface
     */
    private $storage;

    /**
     * @var EventPublisherInterface
     */
    private $eventPublisher;

    /**
     * @var EventNormalizer
     */
    private $eventNormalizer;

    /**
     * @param EventStorageInterface $storage
     * @param EventPublisherInterface $eventPublisher
     * @param EventNormalizer $eventNormalizer
     * @internal Do not instantiate this class directly but inject an instance (or use the EventStoreFactory)
     */
    public function __construct(EventStorageInterface $storage, EventPublisherInterface $eventPublisher, EventNormalizer $eventNormalizer)
    {
        $this->storage = $storage;
        $this->eventPublisher = $eventPublisher;
        $this->eventNormalizer = $eventNormalizer;
    }

    public function load(StreamName $streamName, int $minimumSequenceNumber = 0): EventStream
    {
        try {
            $streamIterator = $this->storage->load($streamName, $minimumSequenceNumber);
            return new EventStream($streamName, $streamIterator, $this->eventNormalizer);
        } catch (\Throwable $exception) {
            throw new \RuntimeException(sprintf('Failed to read Events from stream "%s". Did you run the ./flow eventstore:setup command?', $streamName), 1592394137, $exception);
        }
    }

    /**
     * @param StreamName $streamName
     * @param DomainEvents $events
     * @param int $expectedVersion
     * @throws ConcurrencyException | SerializerException
     */
    public function commit(StreamName $streamName, DomainEvents $events, int $expectedVersion = ExpectedVersion::ANY): void
    {
        if ($events->isEmpty()) {
            return;
        }
        $convertedEvents = [];
        foreach ($events as $event) {
            $eventIdentifier = null;
            $metadata = [];
            if ($event instanceof DecoratedEvent) {
                $eventIdentifier = $event->hasIdentifier() ? $event->getIdentifier() : null;
                $metadata = $event->getMetadata();
                $event = $event->getWrappedEvent();
            }
            $type = $this->eventNormalizer->getEventType($event);
            $data = $this->eventNormalizer->normalize($event);

            if ($eventIdentifier === null) {
                try {
                    $eventIdentifier = (string)Uuid::uuid4();
                } catch (\Exception $exception) {
                    throw new \RuntimeException('Failed to generate UUID for event', 1576421966, $exception);
                }
            }
            $convertedEvents[] = new WritableEvent($eventIdentifier, $type, $data, $metadata);
        }

        $committedEvents = WritableEvents::fromArray($convertedEvents);
        try {
            $this->storage->commit($streamName, $committedEvents, $expectedVersion);
        } catch (ConcurrencyException $exception) {
            // a concurrency exception can happen if the event stream has been modified in the meantime
            throw $exception;
        } catch (\Throwable $exception) {
            throw new \RuntimeException(sprintf('Failed to commit Events to stream "%s". Did you run the ./flow eventstore:setup command?', $streamName), 1592393752, $exception);
        }
        $this->eventPublisher->publish($events);
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
