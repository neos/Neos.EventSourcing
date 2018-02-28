<?php
namespace Neos\EventSourcing\Event;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\Decorator\EventWithMetadataInterface;
use Neos\EventSourcing\EventListener\EventListenerManager;
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\EventSourcing\EventStore\ExpectedVersion;
use Neos\EventSourcing\EventStore\WritableEvent;
use Neos\EventSourcing\EventStore\WritableEvents;
use Neos\EventSourcing\Property\AllowAllPropertiesPropertyMappingConfiguration;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Utility\Algorithms;

/**
 * @Flow\Scope("singleton")
 *
 * Central authority for publishing events to the event store and all subscribed listeners.
 * This class is used by the AbstractEventSourcedRepository, but it can also be used to publish events "manually":
 *
 *   $this->eventPublisher->publish('some-stream', new SomethingHasHappened());
 */
class EventPublisher
{
    /**
     * @var EventStoreManager
     */
    private $eventStoreManager;

    /**
     * @var EventListenerManager
     */
    private $eventListenerManager;

    /**
     * @var PropertyMapper
     */
    private $propertyMapper;

    /**
     * @var EventTypeResolver
     */
    private $eventTypeResolver;

    /**
     * @param EventStoreManager $eventStoreManager
     * @param EventListenerManager $eventListenerManager
     * @param PropertyMapper $propertyMapper
     * @param EventTypeResolver $eventTypeResolver
     */
    public function __construct(EventStoreManager $eventStoreManager, EventListenerManager $eventListenerManager, PropertyMapper $propertyMapper, EventTypeResolver $eventTypeResolver)
    {
        $this->eventStoreManager = $eventStoreManager;
        $this->eventListenerManager = $eventListenerManager;
        $this->propertyMapper = $propertyMapper;
        $this->eventTypeResolver = $eventTypeResolver;
    }

    /**
     * Publish a single $event to the event store and all subscribed listeners
     *
     * @param string $streamName
     * @param EventInterface $event
     * @param int $expectedVersion
     * @return void
     */
    public function publish(string $streamName, EventInterface $event, int $expectedVersion = ExpectedVersion::ANY)
    {
        $this->publishMany($streamName, [$event], $expectedVersion);
    }

    /**
     * Publish the given $events as a single transaction to the event store and all subscribed listeners
     *
     * @param string $streamName
     * @param EventInterface[] $events
     * @param int $expectedVersion
     * @return void
     */
    public function publishMany(string $streamName, array $events, int $expectedVersion = ExpectedVersion::ANY)
    {
        $convertedEvents = new WritableEvents();
        foreach ($events as $event) {
            $metadata = [];
            if ($event instanceof EventWithMetadataInterface) {
                $metadata = $event->getMetadata();
                $event = $event->getEvent();
            }
            $type = $this->eventTypeResolver->getEventType($event);
            $this->emitBeforePublishingEvent($event, $metadata);
            $data = $this->propertyMapper->convert($event, 'array');
            $eventIdentifier = Algorithms::generateUUID();
            $convertedEvents->append(new WritableEvent($eventIdentifier, $type, $data, $metadata));
        }
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $rawEvents = $eventStore->commit($streamName, $convertedEvents, $expectedVersion);

        $configuration = new AllowAllPropertiesPropertyMappingConfiguration();
        foreach ($rawEvents as $rawEvent) {
            $eventClassName = $this->eventTypeResolver->getEventClassNameByType($rawEvent->getType());
            $event = $this->propertyMapper->convert($rawEvent->getPayload(), $eventClassName, $configuration);
            $this->eventListenerManager->invokeSynchronousListeners($event, $rawEvent);
        }
    }

    /**
     * @Flow\Signal
     * @param EventInterface $event
     * @param array $metadata The events metadata, passed as reference so that it can be altered
     * @return void
     */
    protected function emitBeforePublishingEvent(EventInterface $event, array &$metadata)
    {
    }
}
