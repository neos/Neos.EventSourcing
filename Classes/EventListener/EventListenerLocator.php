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
use Neos\Cqrs\EventStore\RawEvent;
use Neos\Cqrs\Exception;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
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
    protected $objectManager;

    /**
     * @var EventTypeResolver
     */
    protected $eventTypeService;

    /**
     * @var array in the format ['<eventType>' => ['<listenerClassName>' => '<listenerMethodName>', '<listenerClassName2>' => '<listenerMethodName2>', ...]]
     */
    protected $mapping = [];

    /**
     * Injecting via setter injection because this resolver must also work during compile time, when proxy classes are
     * not available.
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Injecting via setter injection because this resolver must also work during compile time, when proxy classes are
     * not available.
     *
     * @param EventTypeResolver $eventTypeResolver
     */
    public function injectEventTypeResolver(EventTypeResolver $eventTypeResolver)
    {
        $this->eventTypeService = $eventTypeResolver;
    }

    /**
     * Register event listeners based on annotations
     */
    public function initializeObject()
    {
        $this->mapping = self::detectListeners($this->objectManager);
    }

    /**
     * Returns event listeners for the given event type
     *
     * @param string $eventType
     * @return \callable[]
     */
    public function getListeners(string $eventType): array
    {
        if (!isset($this->mapping[$eventType])) {
            return [];
        }
        $listeners = [];
        array_walk($this->mapping[$eventType], function ($listenerMethodName, $listenerClassName) use (&$listeners) {
            $listeners[] = [$this->objectManager->get($listenerClassName), $listenerMethodName];
        });
        return $listeners;
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
        if (!isset($this->mapping[$eventType][$listenerClassName])) {
            return null;
        }
        return [$this->objectManager->get($listenerClassName), $this->mapping[$eventType][$listenerClassName]];
    }

    /**
     * @param string $listenerClassName
     * @return string[]
     */
    public function getEventTypesByListenerClassName(string $listenerClassName): array
    {
        $eventTypes = [];
        array_walk($this->mapping, function ($listenerMappings, $eventType) use (&$eventTypes, $listenerClassName) {
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
        /** @var EventTypeResolver $eventTypeResolver */
        $eventTypeResolver = $objectManager->get(EventTypeResolver::class);
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

                $eventType = $eventTypeResolver->getEventTypeByClassName($eventClassName);
                if (!isset($listeners[$eventType])) {
                    $listeners[$eventType] = [];
                }
                $listeners[$eventType][$listenerClassName] = $listenerMethodName;
            }
        }

        ksort($listeners);
        return $listeners;
    }
}
