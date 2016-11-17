<?php
namespace Neos\Cqrs\Event;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\EventListener\ActsBeforeInvokingEventListenerMethodsInterface;
use Neos\Cqrs\EventListener\EventListenerManager;
use Neos\Cqrs\EventStore\EventStore;
use Neos\Cqrs\EventStore\ExpectedVersion;
use Neos\Cqrs\EventStore\WritableEvent;
use Neos\Cqrs\EventStore\WritableEvents;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Utility\Algorithms;

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
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var EventListenerManager
     */
    private $eventListenerLocator;

    /**
     * @var PropertyMapper
     */
    private $propertyMapper;

    /**
     * @var EventTypeResolver
     */
    private $eventTypeResolver;

    /**
     * @param EventStore $eventStore
     * @param EventListenerManager $eventListenerLocator
     * @param PropertyMapper $propertyMapper
     * @param EventTypeResolver $eventTypeResolver
     */
    public function __construct(EventStore $eventStore, EventListenerManager $eventListenerLocator, PropertyMapper $propertyMapper, EventTypeResolver $eventTypeResolver)
    {
        $this->eventStore = $eventStore;
        $this->eventListenerLocator = $eventListenerLocator;
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
            $type = $this->eventTypeResolver->getEventType($event);
            $metadata = [];
            $this->emitBeforePublishingEvent($event, $metadata);
            $data = $this->propertyMapper->convert($event, 'array');
            $eventIdentifier = Algorithms::generateUUID();
            $convertedEvents->append(new WritableEvent($eventIdentifier, $type, $data, $metadata));
        }
        $rawEvents = $this->eventStore->commit($streamName, $convertedEvents, $expectedVersion);

        $configuration = new PropertyMappingConfiguration();
        $configuration->allowAllProperties();
        $configuration->forProperty('*')->allowAllProperties();
        foreach ($rawEvents as $rawEvent) {
            $eventClassName = $this->eventTypeResolver->getEventClassNameByType($rawEvent->getType());
            $event = $this->propertyMapper->convert($rawEvent->getPayload(), $eventClassName, $configuration);
            foreach ($this->eventListenerLocator->getSynchronousListenersByEventType($rawEvent->getType()) as $listener) {
                if (is_array($listener) && $listener[0] instanceof ActsBeforeInvokingEventListenerMethodsInterface) {
                    $listener[0]->beforeInvokingEventListenerMethod($listener[1], $event, $rawEvent);
                }
                call_user_func($listener, $event, $rawEvent);
            }
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
