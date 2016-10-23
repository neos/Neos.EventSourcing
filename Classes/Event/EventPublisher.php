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

use Neos\Cqrs\EventStore\EventStore;
use Neos\Cqrs\EventStore\ExpectedVersion;
use Neos\Cqrs\EventStore\WritableEvent;
use Neos\Cqrs\EventStore\WritableEventCollection;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;

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
     * @var EventBus
     */
    private $eventBus;

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
     * @param EventBus $eventBus
     * @param PropertyMapper $propertyMapper
     * @param EventTypeResolver $eventTypeResolver
     */
    public function __construct(EventStore $eventStore, EventBus $eventBus, PropertyMapper $propertyMapper, EventTypeResolver $eventTypeResolver)
    {
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
        $this->propertyMapper = $propertyMapper;
        $this->eventTypeResolver = $eventTypeResolver;
    }


    /**
     * @param string $streamName
     * @param EventWithMetadata $eventWithMetadata
     * @param int $expectedVersion
     */
    public function publish(string $streamName, EventWithMetadata $eventWithMetadata, int $expectedVersion = ExpectedVersion::ANY)
    {
        $this->publishMany($streamName, [$eventWithMetadata], $expectedVersion);
    }

    /**
     * @param string $streamName
     * @param EventWithMetadata[] $eventsWithMetadata
     * @param int $expectedVersion
     */
    public function publishMany(string $streamName, array $eventsWithMetadata, int $expectedVersion = ExpectedVersion::ANY)
    {
        $convertedEvents = [];
        foreach ($eventsWithMetadata as $eventWithMetadata) {
            $type = $this->eventTypeResolver->getEventType($eventWithMetadata->getEvent());
            $data = $this->propertyMapper->convert($eventWithMetadata->getEvent(), 'array');
            $metadata = $this->propertyMapper->convert($eventWithMetadata->getMetadata(), 'array');
            $convertedEvents[] = new WritableEvent($type, $data, $metadata);
        }
        $this->eventStore->commit($streamName, new WritableEventCollection($convertedEvents), $expectedVersion);
        foreach ($eventsWithMetadata as $eventWithMetadata) {
            $this->eventBus->handle($eventWithMetadata);
        }
    }
}
