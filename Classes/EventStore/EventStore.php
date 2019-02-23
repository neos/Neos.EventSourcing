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
use Neos\EventSourcing\Event\Decorator\EventDecoratorUtilities;
use Neos\EventSourcing\EventBus\EventBus;
use Neos\EventSourcing\EventStore\Exception\ConcurrencyException;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\Event\EventTypeResolver;
use Neos\EventSourcing\EventStore\Exception\EventStreamNotFoundException;
use Neos\EventSourcing\EventStore\Storage\EventStorageInterface;

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
     * @var array
     */
    private $postCommitCallbacks = [];

    /**
     * @internal Do not instantiate this class directly but use the EventStoreManager
     * @param EventStorageInterface $storage
     */
    public function __construct(EventStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Registers a callback that is invoked after events have been committed and published.
     *
     * The callback is invoked with the DomainEvents and the resulting WritableEvents as arguments.
     * Example:
     *
     * $eventStore = $this->eventStoreManager->getEventStore('some-es-id');
     * $eventStore->onPostCommit(function(DomainEvents $events, WritableEvents $persistedEvents) {
     *    $this->logger->log('Published ' . $persistedEvents->count() . ' events');
     * });
     *
     * @see commit()
     *
     * @param \Closure $callback
     */
    public function onPostCommit(\Closure $callback): void
    {
        $this->postCommitCallbacks[] = $callback;
    }

    public function load(StreamName $streamName, int $minimumSequenceNumber = 0): EventStream
    {
        $eventStream = $this->storage->load($streamName, $minimumSequenceNumber);
        if (!$eventStream->valid()) {
            throw new EventStreamNotFoundException(sprintf('The event stream "%s" does not appear to be valid.', $streamName), 1477497156);
        }
        return $eventStream;
    }

    /**
     * @param StreamName $streamName
     * @param DomainEvents $events
     * @param int $expectedVersion
     * @throws ConcurrencyException
     */
    public function commit(StreamName $streamName, DomainEvents $events, int $expectedVersion = ExpectedVersion::ANY): void
    {
        if ($events->isEmpty()) {
            return;
        }
        $convertedEvents = [];
        foreach ($events as $event) {
            $eventIdentifier = EventDecoratorUtilities::extractIdentifier($event);
            $metadata = EventDecoratorUtilities::extractMetadata($event);
            $event = EventDecoratorUtilities::extractUndecoratedEvent($event);

            $type = $this->eventTypeResolver->getEventType($event);
            $data = $this->eventNormalizer->normalize($event);

            $convertedEvents[] = new WritableEvent($eventIdentifier, $type, $data, $metadata);
        }

        $committedEvents = WritableEvents::fromArray($convertedEvents);
        $this->storage->commit($streamName, $committedEvents, $expectedVersion);
        $this->eventBus->publish($events);
        foreach ($this->postCommitCallbacks as $callback) {
            $callback($events, $committedEvents);
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
