<?php
declare(strict_types=1);
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

use Neos\EventSourcing\Event\EventTypeResolverInterface;
use Neos\EventSourcing\EventPublisher\DefaultEventPublisherFactory;
use Neos\EventSourcing\EventPublisher\EventPublisherFactoryInterface;
use Neos\EventSourcing\EventStore\Exception\StorageConfigurationException;
use Neos\EventSourcing\EventStore\Storage\EventStorageInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\Exception\UnknownObjectException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * The Event Store manager is responsible for building and Event Store instances as configured.
 * It is used as factory for the EventStore but can also be used explicitly when a certain event store instance is required.
 *
 * @Flow\Scope("singleton")
 */
final class EventStoreFactory
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
     * A list of all initialized event stores, indexed by the "Event Store identifier"
     *
     * @var EventStore[]
     */
    private $initializedEventStores;

    /**
     * @var EventNormalizer
     */
    private $eventNormalizer;

    /**
     * This class is usually not instantiated manually but injected like other singletons
     * Note: Dependencies are constructor-injected in order to ease testing & composition
     *
     * @param ObjectManagerInterface $objectManager
     * @param array $configuration
     * @param EventNormalizer $eventNormalizer
     */
    public function __construct(ObjectManagerInterface $objectManager, array $configuration, EventNormalizer $eventNormalizer)
    {
        $this->objectManager = $objectManager;
        $this->configuration = $configuration;
        $this->eventNormalizer = $eventNormalizer;
    }

    /**
     * Retrieves/builds an EventStore instance with the given identifier
     *
     * @param string $eventStoreIdentifier The unique Event Store identifier as configured
     * @return EventStore
     * @throws \RuntimeException|StorageConfigurationException
     */
    public function create(string $eventStoreIdentifier): EventStore
    {
        if (isset($this->initializedEventStores[$eventStoreIdentifier])) {
            return $this->initializedEventStores[$eventStoreIdentifier];
        }
        if (!isset($this->configuration[$eventStoreIdentifier])) {
            throw new \InvalidArgumentException(sprintf('No Event Store with the identifier "%s" is configured', $eventStoreIdentifier), 1492610857);
        }
        if (!isset($this->configuration[$eventStoreIdentifier]['storage'])) {
            throw new StorageConfigurationException(sprintf('There is no Storage configured for Event Store "%s"', $eventStoreIdentifier), 1492610902);
        }
        $storageClassName = $this->configuration[$eventStoreIdentifier]['storage'];
        $storageOptions = $this->configuration[$eventStoreIdentifier]['storageOptions'] ?? [];

        try {
            /** @noinspection PhpMethodParametersCountMismatchInspection */
            $storageInstance = $this->objectManager->get($storageClassName, $storageOptions);
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (UnknownObjectException $exception) {
            throw new StorageConfigurationException(sprintf('The configured Storage "%s" for Event Store "%s" is unknown', $storageClassName, $eventStoreIdentifier), 1570194203, $exception);
        }
        if (!$storageInstance instanceof EventStorageInterface) {
            throw new StorageConfigurationException(sprintf('The configured Storage "%s" for Event Store "%s" does not implement the %s', $storageClassName, $eventStoreIdentifier, EventStorageInterface::class), 1492610908);
        }

        if (isset($this->configuration[$eventStoreIdentifier]['eventPublisherFactory'])) {
            $eventPublisherFactoryClassName = $this->configuration[$eventStoreIdentifier]['eventPublisherFactory'];
            try {
                $eventPublisherFactory = $this->objectManager->get($eventPublisherFactoryClassName);
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (UnknownObjectException $exception) {
                throw new StorageConfigurationException(sprintf('The configured eventPublisherFactory "%s" for Event Store "%s" is unknown', $eventPublisherFactoryClassName, $eventStoreIdentifier), 1579098129, $exception);
            }
            if (!$eventPublisherFactory instanceof EventPublisherFactoryInterface) {
                throw new StorageConfigurationException(sprintf('The configured eventPublisherFactory "%s" for Event Store "%s" does not implement the %s', $eventPublisherFactoryClassName, $eventStoreIdentifier, EventPublisherFactoryInterface::class), 1579101180);
            }
        } else {
            $eventPublisherFactory = $this->objectManager->get(DefaultEventPublisherFactory::class);
        }

        $eventPublisher = $eventPublisherFactory->create($eventStoreIdentifier);
        $this->initializedEventStores[$eventStoreIdentifier] = new EventStore($storageInstance, $eventPublisher, $this->eventNormalizer);
        return $this->initializedEventStores[$eventStoreIdentifier];
    }
}
