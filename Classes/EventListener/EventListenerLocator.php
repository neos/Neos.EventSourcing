<?php
namespace Neos\Cqrs\EventListener;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Event\EventInterface;
use Neos\Cqrs\Event\EventTypeResolver;
use Neos\Cqrs\EventStore\EventStore;
use Neos\Cqrs\EventStore\RawEvent;
use Neos\Cqrs\Exception;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\ObjectManagement\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * EventListenerLocator
 *
 * @Flow\Scope("singleton")
 */
class EventListenerLocator
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var EventTypeResolver
     */
    private $eventTypeService;

    /**
     * @var array in the format ['<eventClassName>' => ['<listenerClassName>' => '<listenerMethodName>', '<listenerClassName2>' => '<listenerMethodName2>', ...]]
     */
    private $eventClassNamesAndListeners = [];

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param EventTypeResolver $eventTypeService
     * @param EventStore $eventStore
     */
    public function __construct(ObjectManagerInterface $objectManager, EventTypeResolver $eventTypeService, EventStore $eventStore)
    {
        $this->objectManager = $objectManager;
        $this->eventTypeService = $eventTypeService;
        $this->eventStore = $eventStore;
    }

    /**
     * Register event listeners based on annotations
     */
    public function initializeObject()
    {
        $this->eventClassNamesAndListeners = self::detectListeners($this->objectManager);
    }

    /**
     * Returns all known event listeners
     *
     * @return \callable[]
     */
    public function getListeners(): array
    {
        $listeners = [];
        foreach ($this->eventClassNamesAndListeners as $eventClassName => $listenersForEventType) {
            array_walk($this->eventClassNamesAndListeners[$eventClassName], function ($listenerMethodName, $listenerClassName) use (&$listeners) {
                $listeners[] = [$this->objectManager->get($listenerClassName), $listenerMethodName];
            });
        }
        return $listeners;
    }

    /**
     * Returns event listeners for the given event type
     *
     * @param string $eventType
     * @return \callable[]
     */
    public function getListenersByEventType(string $eventType): array
    {
        $eventClassName = $this->eventTypeService->getEventClassNameByType($eventType);
        if (!isset($this->eventClassNamesAndListeners[$eventClassName])) {
            return [];
        }
        $listeners = [];
        array_walk($this->eventClassNamesAndListeners[$eventClassName], function ($listenerMethodName, $listenerClassName) use (&$listeners) {
            $listeners[] = [$this->objectManager->get($listenerClassName), $listenerMethodName];
        });
        return $listeners;
    }

    /**
     * Returns all known synchronous event listeners
     *
     * @return \callable[]
     */
    public function getSynchronousListeners(): array
    {
        return array_filter($this->getListeners(), function (array $listener) {
            return (!is_array($listener) || !$listener[0] instanceof AsynchronousEventListenerInterface);
        });
    }

    /**
     * Returns synchronous event listeners for the given event type
     *
     * @param string $eventType
     * @return \callable[]
     */
    public function getSynchronousListenersByEventType(string $eventType): array
    {
        return array_filter($this->getListenersByEventType($eventType), function (array $listener) {
            return (!is_array($listener) || !$listener[0] instanceof AsynchronousEventListenerInterface);
        });
    }

    /**
     * Returns all known asynchronous event listeners (implementing AsyncEventListenerInterface)
     *
     * @return \callable[]
     */
    public function getAsynchronousListeners(): array
    {
        return array_filter($this->getListeners(), function (array $listener) {
            return (is_array($listener) && $listener[0] instanceof AsynchronousEventListenerInterface);
        });
    }

    /**
     * Returns asynchronous event listeners (implementing AsyncEventListenerInterface) for the given event type
     *
     * @param string $eventType
     * @return \callable[]
     */
    public function getAsynchronousListenersByEventType(string $eventType): array
    {
        return array_filter($this->getListenersByEventType($eventType), function (array $listener) {
            return (is_array($listener) && $listener[0] instanceof AsynchronousEventListenerInterface);
        });
    }

    /**
     * Returns a single listener for the given $eventType and $listenerClassName, or null if the given listener
     * does not handle events of the specified type.
     *
     * @param string $eventType
     * @param string $listenerClassName
     * @return \callable|null
     */
    public function getListener(string $eventType, string $listenerClassName)
    {
        $eventClassName = $this->eventTypeService->getEventClassNameByType($eventType);
        if (!isset($this->eventClassNamesAndListeners[$eventClassName][$listenerClassName])) {
            return null;
        }
        return [$this->objectManager->get($listenerClassName), $this->eventClassNamesAndListeners[$eventClassName][$listenerClassName]];
    }

    /**
     * @param string $listenerClassName
     * @return string[]
     */
    public function getEventTypesByListenerClassName(string $listenerClassName): array
    {
        $eventTypes = [];
        array_walk($this->eventClassNamesAndListeners, function ($listenerMappings, $eventClassName) use (&$eventTypes, $listenerClassName) {
            $eventType = $this->eventTypeService->getEventTypeByClassName($eventClassName);
            foreach (array_keys($listenerMappings) as $listenerMappingClassName) {
                if ($listenerMappingClassName === $listenerClassName) {
                    $eventTypes[] = $eventType;
                }
            }
        });
        return $eventTypes;
    }

    /**
     * Detects and collects all existing event listener classes
     *
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @throws Exception
     * @Flow\CompileStatic
     */
    protected static function detectListeners(ObjectManagerInterface $objectManager): array
    {
        $listeners = [];
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);
        foreach ($reflectionService->getAllImplementationClassNamesForInterface(EventListenerInterface::class) as $listenerClassName) {
            foreach (get_class_methods($listenerClassName) as $listenerMethodName) {
                preg_match('/^when[A-Z].*$/', $listenerMethodName, $matches);
                if (!isset($matches[0])) {
                    continue;
                }
                $parameters = array_values($reflectionService->getMethodParameters($listenerClassName, $listenerMethodName));

                if (!isset($parameters[0])) {
                    throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, must accept an EventInterface and optionally a RawEvent', $listenerClassName, $listenerMethodName), 1472500228);
                }
                $eventClassName = $parameters[0]['class'];
                if (!$reflectionService->isClassImplementationOf($eventClassName, EventInterface::class)) {
                    throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, the first parameter should be cast to an implementation of EventInterface', $listenerClassName, $listenerMethodName), 1472504443);
                }

                if (isset($parameters[1])) {
                    $rawEventDataType = $parameters[1]['class'];
                    if ($rawEventDataType !== RawEvent::class) {
                        throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, the second parameter should be cast to RawEvent but expects an instance of "%s"', $listenerClassName, $listenerMethodName, $rawEventDataType), 1472504303);
                    }
                }
                $expectedMethodName = 'when' . (new \ReflectionClass($eventClassName))->getShortName();
                if ($expectedMethodName !== $listenerMethodName) {
                    throw new Exception(sprintf('Invalid listener in %s::%s the method name is expected to be "%s"', $listenerClassName, $listenerMethodName, $expectedMethodName), 1476442394);
                }

                if (!isset($listeners[$eventClassName])) {
                    $listeners[$eventClassName] = [];
                }
                $listeners[$eventClassName][$listenerClassName] = $listenerMethodName;
            }
        }

        return $listeners;
    }
}
