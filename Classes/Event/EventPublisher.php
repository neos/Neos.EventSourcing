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
use Neos\Cqrs\EventListener\AsynchronousEventListenerInterface;
use Neos\Cqrs\EventListener\EventListenerLocator;
use Neos\Cqrs\EventStore\EventStore;
use Neos\Cqrs\EventStore\EventTypesFilter;
use Neos\Cqrs\EventStore\Exception\EventStreamNotFoundException;
use Neos\Cqrs\EventStore\ExpectedVersion;
use Neos\Cqrs\EventStore\WritableEvent;
use Neos\Cqrs\EventStore\WritableEvents;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
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
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var EventListenerLocator
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
     * @param EventListenerLocator $eventListenerLocator
     * @param PropertyMapper $propertyMapper
     * @param EventTypeResolver $eventTypeResolver
     */
    public function __construct(EventStore $eventStore, EventListenerLocator $eventListenerLocator, PropertyMapper $propertyMapper, EventTypeResolver $eventTypeResolver)
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
                    $listener[0]->beforeInvokingEventListenerMethod($event, $rawEvent);
                }
                call_user_func($listener, $event, $rawEvent);
            }
        }
    }

    /**
     * Iterate over all relevant event listeners and play back events to them which haven't been applied previously.
     *
     * @param \Closure $progressCallback Call back which is triggered on each event listener being invoked
     * @return int
     */
    public function catchUp(\Closure $progressCallback): int
    {
        $distinctListenerObjectsByClassName = [];
        foreach ($this->eventListenerLocator->getAsynchronousListeners() as $listener) {
            if (!is_array($listener)) {
                continue;
            }
            $distinctListenerObjectsByClassName[get_class($listener[0])] = $listener[0];
        }

        $eventCount = 0;
        foreach ($distinctListenerObjectsByClassName as $listenerClassName => $listenerObject) {
            $lastAppliedSequenceNumber = $listenerObject->getHighestAppliedSequenceNumber();

            $filter = new EventTypesFilter($this->eventListenerLocator->getEventTypesByListenerClassName($listenerClassName), $lastAppliedSequenceNumber + 1);
            try {
                $eventStream = $this->eventStore->get($filter);
            } catch (EventStreamNotFoundException $exception) {
                continue;
            }

            foreach ($eventStream as $sequenceNumber => $eventAndRawEvent) {
                $event = $eventAndRawEvent->getEvent();
                $rawEvent = $eventAndRawEvent->getRawEvent();
                $listener = $this->eventListenerLocator->getListener($rawEvent->getType(), $listenerClassName);

                /** @var AsynchronousEventListenerInterface $listenerObject */
                $listenerObject = $listener[0];
                if ($listenerObject instanceof ActsBeforeInvokingEventListenerMethodsInterface) {
                    $listenerObject->beforeInvokingEventListenerMethod($event, $rawEvent);
                }

                $progressCallback($listenerClassName, $rawEvent->getType(), $eventCount);
                call_user_func($listener, $event, $rawEvent);

                $eventCount ++;
                $listenerObject->saveHighestAppliedSequenceNumber($sequenceNumber);
            }
        }
        return $eventCount;
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
