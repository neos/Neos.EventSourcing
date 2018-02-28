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

use Neos\EventSourcing\Event\EventInterface;
use Neos\EventSourcing\Event\EventTypeResolver;
use Neos\EventSourcing\EventListener\Exception\EventCantBeAppliedException;
use Neos\EventSourcing\EventStore\EventStream;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\EventSourcing\Exception;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;

/**
 * Central authority for Event Listeners
 *
 * @Flow\Scope("singleton")
 */
class EventListenerManager
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
     * @param ObjectManagerInterface $objectManager
     * @param EventTypeResolver $eventTypeService
     */
    public function __construct(ObjectManagerInterface $objectManager, EventTypeResolver $eventTypeService)
    {
        $this->objectManager = $objectManager;
        $this->eventTypeService = $eventTypeService;
    }

    /**
     * Register event listeners based on annotations
     */
    public function initializeObject()
    {
        $this->eventClassNamesAndListeners = self::detectListeners($this->objectManager);
    }

    /**
     * Iterates through the given $eventStream and invokes the corresponding "when*" method on the $eventListener
     *
     * @param EventListenerInterface $eventListener The EventListener class that should handle events
     * @param EventStream $eventStream The events to be handled
     * @param \Closure|null $progressCallback Optional callback that is invoked after each handled event (for debugging, progress notification, ...)
     */
    public function invokeListeners(EventListenerInterface $eventListener, EventStream $eventStream, \Closure $progressCallback = null)
    {
        foreach ($eventStream as $sequenceNumber => $eventAndRawEvent) {
            $this->invokeListener($eventListener, $eventAndRawEvent->getEvent(), $eventAndRawEvent->getRawEvent());
            if ($progressCallback !== null) {
                call_user_func($progressCallback, $eventAndRawEvent->getRawEvent());
            }
        }
    }

    /**
     * Handles the given $event on all synchronous EventListeners
     *
     * @param EventInterface $event
     * @param RawEvent $rawEvent
     */
    public function invokeSynchronousListeners(EventInterface $event, RawEvent $rawEvent)
    {
        $eventClassName = $this->eventTypeService->getEventClassNameByType($rawEvent->getType());
        if (!isset($this->eventClassNamesAndListeners[$eventClassName])) {
            return;
        }
        foreach (array_keys($this->eventClassNamesAndListeners[$eventClassName]) as $listenerClassName) {
            if (is_subclass_of($listenerClassName, AsynchronousEventListenerInterface::class)) {
                return;
            }
            /** @var EventListenerInterface $eventListener */
            $eventListener = $this->objectManager->get($listenerClassName);
            $this->invokeListener($eventListener, $event, $rawEvent);
        }
    }

    /**
     * Invokes the "when*()" method of the given $eventListener for the specified $event
     * Additionally this invokes beforeInvokingEventListenerMethod(), saveHighestAppliedSequenceNumber() and afterInvokingEventListenerMethod()
     * if the EventListener implements the corresponding interfaces
     *
     * @param EventListenerInterface $eventListener
     * @param EventInterface $event
     * @param RawEvent $rawEvent
     * @throws EventCantBeAppliedException
     */
    private function invokeListener(EventListenerInterface $eventListener, EventInterface $event, RawEvent $rawEvent)
    {
        if ($eventListener instanceof ActsBeforeInvokingEventListenerMethodsInterface) {
            $eventListener->beforeInvokingEventListenerMethod($event, $rawEvent);
        }
        $eventClassName = $this->eventTypeService->getEventClassNameByType($rawEvent->getType());
        $eventListenerMethodName = $this->eventClassNamesAndListeners[$eventClassName][get_class($eventListener)];
        try {
            call_user_func([$eventListener, $eventListenerMethodName], $event, $rawEvent);
        } catch (\Exception $exception) {
            throw new EventCantBeAppliedException(sprintf('Event "%s" (at sequence number %d) could not be applied to %s::%s()', $rawEvent->getType(), $rawEvent->getSequenceNumber(), $eventClassName, $eventListenerMethodName), 1507113406, $exception, $rawEvent);
        }
        if ($eventListener instanceof AsynchronousEventListenerInterface) {
            $eventListener->saveHighestAppliedSequenceNumber($rawEvent->getSequenceNumber());
        }
        if ($eventListener instanceof ActsAfterInvokingEventListenerMethodsInterface) {
            $eventListener->afterInvokingEventListenerMethod($event, $rawEvent);
        }
    }

    /**
     * @return string[]
     */
    public function getAsynchronousListenerClassNames(): array
    {
        $asynchronousListenerClassNames = [];
        array_walk($this->eventClassNamesAndListeners, function ($listenerMappings) use (&$asynchronousListenerClassNames) {
            foreach (array_keys($listenerMappings) as $listenerMappingClassName) {
                if (!in_array($listenerMappingClassName, $asynchronousListenerClassNames) && is_subclass_of($listenerMappingClassName, AsynchronousEventListenerInterface::class)) {
                    $asynchronousListenerClassNames[] = $listenerMappingClassName;
                }
            }
        });
        return $asynchronousListenerClassNames;
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
     * @return array in the format ['<eventClassName>' => ['<listenerClassName>' => '<listenerMethodName>', '<listenerClassName2>' => '<listenerMethodName2>', ...]]
     * @throws Exception
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
                    throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, must accept an EventInterface and optionally a RawEvent', $listenerClassName, $listenerMethodName), 1472500228);
                }
                $eventClassName = $parameters[0]['class'];
                if (!$reflectionService->isClassImplementationOf($eventClassName, EventInterface::class)) {
                    throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, the first parameter should be an implementation of EventInterface but it expects an instance of "%s"', $listenerClassName, $listenerMethodName, $eventClassName), 1472504443);
                }

                if (isset($parameters[1])) {
                    $rawEventDataType = $parameters[1]['class'];
                    if ($rawEventDataType !== RawEvent::class) {
                        throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong. If the second parameter is present, it has to be a RawEvent but it expects an instance of "%s"', $listenerClassName, $listenerMethodName, $rawEventDataType), 1472504303);
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
                $listenersFoundInClass = true;
            }
            if (!$listenersFoundInClass) {
                throw new Exception(sprintf('No listener methods have been detected in listener class %s. A listener has the signature "public function when<EventClass>(<EventClass> $event) {}" and every EventListener class has to implement at least one listener!', $listenerClassName), 1498123537);
            }
        }

        return $listeners;
    }
}
