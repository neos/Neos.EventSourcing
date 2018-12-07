<?php
namespace Neos\EventSourcing\EventListener;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;

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
     * @var array in the format ['<eventClassName>' => ['<listenerClassName>' => '<listenerMethodName>', '<listenerClassName2>' => '<listenerMethodName2>', ...]]
     */
    private $eventClassNamesAndListeners = [];

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Register event listeners based on annotations
     * @throws \ReflectionException
     */
    public function initializeObject(): void
    {
        $this->eventClassNamesAndListeners = self::detectListeners($this->objectManager);
    }

    /**
     * Returns all known event listeners that listen to events of the given $eventClassName
     *
     * @param string $eventClassName
     * @return \callable[]
     */
    public function getListenersForEventClassName(string $eventClassName): array
    {
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
     * @param string $eventClassName
     * @return string[]
     */
    public function getListenerClassNamesForEventClassName(string $eventClassName): array
    {

        if (!isset($this->eventClassNamesAndListeners[$eventClassName])) {
            return [];
        }
        return array_keys($this->eventClassNamesAndListeners[$eventClassName]);
    }

    /**
     * Returns a single listener for the given $eventClassName and $listenerClassName, or null if the given listener
     * does not handle events of the specified type.
     *
     * @param string $eventClassName
     * @param string $listenerClassName
     * @return \callable|null
     */
    public function getListener(string $eventClassName, string $listenerClassName): ?callable
    {
        if (!isset($this->eventClassNamesAndListeners[$eventClassName][$listenerClassName])) {
            return null;
        }
        return [$this->objectManager->get($listenerClassName), $this->eventClassNamesAndListeners[$eventClassName][$listenerClassName]];
    }

    public function getEventClassNamesByListenerClassName($listenerClassName): array
    {
        $eventClassNames = [];
        array_walk($this->eventClassNamesAndListeners, function ($listenerMappings, $eventClassName) use (&$eventClassNames, $listenerClassName) {
            foreach (array_keys($listenerMappings) as $listenerMappingClassName) {
                if ($listenerMappingClassName === $listenerClassName) {
                    $eventClassNames[] = $eventClassName;
                }
            }
        });
        return $eventClassNames;
    }

    /**
     * Detects and collects all existing event listener classes
     *
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @throws \ReflectionException
     * @Flow\CompileStatic
     */
    protected static function detectListeners(ObjectManagerInterface $objectManager): array
    {
        $listeners = [];
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);
        foreach ($reflectionService->getAllImplementationClassNamesForInterface(EventListenerInterface::class) as $listenerClassName) {
            $listenersFoundInClass = false;
            foreach (get_class_methods($listenerClassName) as $listenerMethodName) {
                preg_match('/^when[A-Z].*$/', $listenerMethodName, $matches);
                if (!isset($matches[0])) {
                    continue;
                }
                $parameters = array_values($reflectionService->getMethodParameters($listenerClassName, $listenerMethodName));

                if (!isset($parameters[0])) {
                    throw new \RuntimeException(sprintf('Invalid listener in %s::%s the method signature is wrong, must accept an EventInterface and optionally a RawEvent', $listenerClassName, $listenerMethodName), 1472500228);
                }
                $eventClassName = $parameters[0]['class'];
                if (!$reflectionService->isClassImplementationOf($eventClassName, DomainEventInterface::class)) {
                    throw new \RuntimeException(sprintf('Invalid listener in %s::%s the method signature is wrong, the first parameter should be an implementation of EventInterface but it expects an instance of "%s"', $listenerClassName, $listenerMethodName, $eventClassName), 1472504443);
                }

                if (isset($parameters[1])) {
                    $rawEventDataType = $parameters[1]['class'];
                    if ($rawEventDataType !== RawEvent::class) {
                        throw new \RuntimeException(sprintf('Invalid listener in %s::%s the method signature is wrong. If the second parameter is present, it has to be a RawEvent but it expects an instance of "%s"', $listenerClassName, $listenerMethodName, $rawEventDataType), 1472504303);
                    }
                }
                $expectedMethodName = 'when' . (new \ReflectionClass($eventClassName))->getShortName();
                if ($expectedMethodName !== $listenerMethodName) {
                    throw new \RuntimeException(sprintf('Invalid listener in %s::%s the method name is expected to be "%s"', $listenerClassName, $listenerMethodName, $expectedMethodName), 1476442394);
                }

                if (!isset($listeners[$eventClassName])) {
                    $listeners[$eventClassName] = [];
                }
                $listeners[$eventClassName][$listenerClassName] = $listenerMethodName;
                $listenersFoundInClass = true;
            }
            if (!$listenersFoundInClass) {
                throw new \RuntimeException(sprintf('No listener methods have been detected in listener class %s. A listener has the signature "public function when<EventClass>(<EventClass> $event) {}" and every EventListener class has to implement at least one listener!', $listenerClassName), 1498123537);
            }
        }

        return $listeners;
    }

}
