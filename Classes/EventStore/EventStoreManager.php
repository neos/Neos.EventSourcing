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
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class EventStoreManager
{

    /**
     * @var array
     * @Flow\InjectConfiguration(path="EventStore.stores")
     */
    protected $configuration;

    /**
     * @var array
     */
    protected $eventStoreIdentifiersPerBoundedContext = null;

    /**
     * @var array<EventStore>
     */
    protected $initializedEventStores;

    public function getEventStoreForAggregateStreamName($streamName) {
        // TODO
        return $this->getEventStoreForEventTypes([$streamName]);
    }

    public function getEventStoreForEventTypes(array $eventTypes) : EventStore {
        $boundedContexts = [];
        foreach ($eventTypes as $eventName) {
            list($boundedContext) = explode(':', $eventName);
            $boundedContexts[$boundedContext] = $boundedContext;
        }

        $result = null;
        foreach ($boundedContexts as $boundedContext) {
            $tempResult = $this->getEventStoreForBoundedContext($boundedContext);

            if ($result !== NULL && $tempResult !== $result) {
                throw new StorageConfigurationException("TODO (excpetion type): different event stores per bounded context." . implode(", ", $eventTypes));
            }
            $result = $tempResult;
        }
        return $result;
    }

    public function getEventStoreForBoundedContext($boundedContext)
    {
        $eventStoreIdentifier = $this->resolveEventStoreIdentifierForBoundedContext($boundedContext);
        return $this->getEventStore($eventStoreIdentifier);
    }

    protected function getEventStore($eventStoreIdentifier)
    {
        if (!isset($this->initializedEventStores[$eventStoreIdentifier])) {
            $storage = $this->configuration[$eventStoreIdentifier]['storage'];
            $storageOptions = $this->configuration[$eventStoreIdentifier]['storageOptions'];

            $storageInstance = new $storage($storageOptions);
            $this->initializedEventStores[$eventStoreIdentifier] = [
                'storage' => $storageInstance,
                'eventStore' => new EventStore($storageInstance)
            ];
        }

        return $this->initializedEventStores[$eventStoreIdentifier]['eventStore'];
    }

    public function initializeFromConfig()
    {
        $this->eventStoreIdentifiersPerBoundedContext = [];
        foreach ($this->configuration as $eventStoreIdentifier => $eventStoreConfiguration) {
            if (!isset($eventStoreConfiguration['boundedContexts'])) {
                throw new StorageConfigurationException(sprintf('The event store "%s" has no boundedContexts assigned. Please configure some.', $eventStoreIdentifier), 1479213813);
            }
            foreach ($eventStoreConfiguration['boundedContexts'] as $boundedContext => $isActive) {
                if (!$isActive) {
                    continue;
                }

                $this->eventStoreIdentifiersPerBoundedContext[$boundedContext] = $eventStoreIdentifier;
            }
        }

        if (!isset($this->eventStoreIdentifiersPerBoundedContext['*'])) {
            throw new StorageConfigurationException('No event store found for fallback bounded context "*"', 1479214520);
        }
    }

    private function resolveEventStoreIdentifierForBoundedContext($boundedContext)
    {
        if (!$this->eventStoreIdentifiersPerBoundedContext) {
            $this->initializeFromConfig();
        }

        return $this->eventStoreIdentifiersPerBoundedContext[$boundedContext] ?? $this->eventStoreIdentifiersPerBoundedContext['*'];
    }

    public function getAllConfiguredStorageBackends()
    {
        $storages = [];
        foreach (array_keys($this->configuration) as $eventStoreIdentifier) {
            $this->getEventStore($eventStoreIdentifier); // init if not exists


            $storages[$eventStoreIdentifier] = $this->initializedEventStores[$eventStoreIdentifier]['storage'];
        }
        return $storages;
    }
}
