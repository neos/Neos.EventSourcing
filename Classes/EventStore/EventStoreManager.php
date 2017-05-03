<?php
namespace Neos\EventSourcing\EventStore;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\EventStore\Exception\StorageConfigurationException;
use Neos\EventSourcing\EventStore\Storage\EventStorageInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * The Event Store manager is responsible for building and Event Store instances as configured.
 * Whenever an Event Store is needed, it should be retrieved through this class.
 *
 * @Flow\Scope("singleton")
 */
final class EventStoreManager
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @var string[]
     */
    private $eventStoreIdentifiersPerBoundedContext = null;

    /**
     * A list of all initialized event stores, indexed by the "Event Store identifier"
     *
     * @var EventStore[]
     */
    private $initializedEventStores;

    /**
     * This class is usually not instantiated manually but injected like other singletons
     * Note: ObjectManager and configuration is constructor-injected in order to ease testing & composition
     *
     * @param ObjectManagerInterface $objectManager
     * @param array $configuration
     */
    public function __construct(ObjectManagerInterface $objectManager, array $configuration)
    {
        $this->objectManager = $objectManager;
        $this->configuration = $configuration;
    }

    /**
     * Initializes the Event Store adapters as configured
     * This also validates the Event Store configuration
     *
     * @return void
     * @throws StorageConfigurationException
     */
    private function initialize()
    {
        if ($this->eventStoreIdentifiersPerBoundedContext !== null) {
            return;
        }
        $this->eventStoreIdentifiersPerBoundedContext = [];
        foreach ($this->configuration as $eventStoreIdentifier => $eventStoreConfiguration) {
            if (!isset($eventStoreConfiguration['boundedContexts']) || empty($eventStoreConfiguration['boundedContexts'])) {
                throw new StorageConfigurationException(sprintf('The Event Store "%s" has no Bounded Context assigned. Please configure some.', $eventStoreIdentifier), 1479213813);
            }
            foreach ($eventStoreConfiguration['boundedContexts'] as $boundedContext => $isActive) {
                if (!$isActive) {
                    continue;
                }
                if (isset($this->eventStoreIdentifiersPerBoundedContext[$boundedContext])) {
                    throw new StorageConfigurationException(sprintf('The Event Stores "%s" and "%s" are both configured for the Bounded Context "%s" but overlaps are not supported.', $this->eventStoreIdentifiersPerBoundedContext[$boundedContext], $eventStoreIdentifier, $boundedContext), 1492434176);
                }
                $this->eventStoreIdentifiersPerBoundedContext[$boundedContext] = $eventStoreIdentifier;
            }
        }

        if (!isset($this->eventStoreIdentifiersPerBoundedContext['*'])) {
            throw new StorageConfigurationException('No Event Store found for fallback Bounded Context "*"', 1479214520);
        }
    }

    /**
     * Retrieves/builds an EventStore instance with the given identifier
     *
     * @param string $eventStoreIdentifier The unique Event Store identifier as configured
     * @return EventStore
     * @throws \RuntimeException|StorageConfigurationException
     */
    public function getEventStore(string $eventStoreIdentifier): EventStore
    {
        $this->initialize();
        if (isset($this->initializedEventStores[$eventStoreIdentifier])) {
            return $this->initializedEventStores[$eventStoreIdentifier];
        }
        if (!isset($this->configuration[$eventStoreIdentifier])) {
            throw new \RuntimeException(sprintf('No Event Store with the identifier "%s" is configured', $eventStoreIdentifier), 1492610857);
        }
        if (!isset($this->configuration[$eventStoreIdentifier]['storage'])) {
            throw new StorageConfigurationException(sprintf('There is no Storage configured for Event Store "%s"', $eventStoreIdentifier), 1492610902);
        }
        $storageClassName = $this->configuration[$eventStoreIdentifier]['storage'];
        $storageOptions = $this->configuration[$eventStoreIdentifier]['storageOptions'] ?? [];

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $storageInstance = $this->objectManager->get($storageClassName, $storageOptions);
        if (!$storageInstance instanceof EventStorageInterface) {
            throw new StorageConfigurationException(sprintf('The configured Storage for Event Store "%s" does not implement the EventStorageInterface', $eventStoreIdentifier), 1492610908);
        }
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $this->initializedEventStores[$eventStoreIdentifier] = $this->objectManager->get(EventStore::class, $storageInstance);

        return $this->initializedEventStores[$eventStoreIdentifier];
    }

    /**
     * Retrieves/builds an EventStore instance matching the given stream name
     *
     * @param string $streamName The stream name can be any string, but usually it has the format "<Bounded.Context>:<Aggregate>-<Identifier>"
     * @return EventStore
     */
    public function getEventStoreForStreamName(string $streamName): EventStore
    {
        $boundedContext = $this->extractBoundedContextFromDesignator($streamName);
        return $this->getEventStoreForBoundedContext($boundedContext);
    }

    /**
     * Retrieves/builds an EventStore instance matching the given event types
     *
     * @param string $listenerClassName The fully qualified class name of the EventListener (or Projector)
     * @return EventStore
     */
    public function getEventStoreForEventListener(string $listenerClassName)
    {
        $boundedContext = $this->objectManager->getPackageKeyByObjectName($listenerClassName) ?? '';
        return $this->getEventStoreForBoundedContext($boundedContext);
    }

    /**
     * Retrieves/builds an EventStore instance matching the given Bounded Context
     *
     * @param string $boundedContext The Bounded Context can be any string, for example a package key like "Some.Package"
     * @return EventStore
     */
    public function getEventStoreForBoundedContext(string $boundedContext): EventStore
    {
        $this->initialize();
        $eventStoreIdentifier = $this->eventStoreIdentifiersPerBoundedContext[$boundedContext] ?? $this->eventStoreIdentifiersPerBoundedContext['*'];
        return $this->getEventStore($eventStoreIdentifier);
    }

    /**
     * Retrieves/builds all configured EventStore instances
     *
     * Note: As this method instantiates all configured Event Store adapters it should only be used for monitoring/testing
     *
     * @return EventStore[]
     */
    public function getAllEventStores(): array
    {
        $this->initialize();
        $eventStores = [];
        foreach ($this->eventStoreIdentifiersPerBoundedContext as $eventStoreIdentifier) {
            // Note: getEventStore() will initialize the given Event Store
            $eventStores[$eventStoreIdentifier] = $this->getEventStore($eventStoreIdentifier);
        }
        return $eventStores;
    }

    /**
     * Determines the Bounded Context identifier from a fully qualified designator (event type, stream name, ...)
     * The BC is the substring of the designator before the first colon.
     * If the designator doesn't contain any colons, the full designator is considered the BC!!
     *
     * Examples:
     *
     * "Foo:Bar" => "Foo"
     * "Foo.Bar:Baz:Foos" => "Foo.Bar"
     * "Foo.Bar" => "Foo.Bar"
     *
     * @param string $designator
     * @return string
     */
    private function extractBoundedContextFromDesignator(string $designator): string
    {
        $boundedContext = strstr($designator, ':', true);
        return $boundedContext !== false ? $boundedContext : $designator;
    }
}
