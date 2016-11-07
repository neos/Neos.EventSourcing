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

use Neos\Cqrs\EventListener\EventListenerLocator;
use Neos\Cqrs\EventStore\EventStore;
use Neos\Cqrs\EventStore\ExpectedVersion;
use Neos\Cqrs\EventStore\WritableEvent;
use Neos\Cqrs\EventStore\WritableEvents;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfiguration;

/**
 * @Flow\Scope("singleton")
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
     * @param string $streamName
     * @param EventInterface $event
     * @param int $expectedVersion
     */
    public function publish(string $streamName, EventInterface $event, int $expectedVersion = ExpectedVersion::ANY)
    {
        $this->publishMany($streamName, [$event], $expectedVersion);
    }

    /**
     * @param string $streamName
     * @param EventInterface[] $events
     * @param int $expectedVersion
     */
    public function publishMany(string $streamName, array $events, int $expectedVersion = ExpectedVersion::ANY)
    {
        $convertedEvents = new WritableEvents();
        foreach ($events as $event) {
            $type = $this->eventTypeResolver->getEventType($event);
            $metadata = [];
            $this->emitPublishEvent($event, $metadata);
            $data = $this->propertyMapper->convert($event, 'array');
            $convertedEvents->append(new WritableEvent($type, $data, $metadata));
        }
        $storedEvents = $this->eventStore->commit($streamName, $convertedEvents, $expectedVersion);

        $configuration = new PropertyMappingConfiguration();
        $configuration->allowAllProperties();
        $configuration->forProperty('*')->allowAllProperties();
        foreach ($storedEvents as $storedEvent) {
            $eventClassName = $this->eventTypeResolver->getEventClassNameByType($storedEvent->getType());
            $event = $this->propertyMapper->convert($storedEvent->getPayload(), $eventClassName, $configuration);
            foreach ($this->eventListenerLocator->getListeners($storedEvent->getType()) as $listener) {
                call_user_func($listener, $event, $storedEvent);
            }
        }
    }

    /**
     * @Flow\Signal
     * @param EventInterface $event
     * @param array $metadata The events metadata, passed as reference so that it can be altered
     * @return void
     */
    protected function emitPublishEvent(EventInterface $event, array &$metadata)
    {
    }
}
