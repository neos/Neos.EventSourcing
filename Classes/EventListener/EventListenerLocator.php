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
use Neos\Cqrs\Event\EventTypeService;
use Neos\Cqrs\Exception;
use Neos\Cqrs\Message\MessageMetadata;
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
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var EventTypeService
     * @Flow\Inject
     */
    protected $eventTypeService;

    /**
     * @var array
     */
    protected $mapping = [];

    /**
     * Register event listeners based on annotations
     */
    public function initializeObject()
    {
        $this->mapping = self::detectListeners($this->objectManager);
    }

    /**
     * Returns event listeners for the given event (type)
     *
     * @param EventInterface $event
     * @return \Generator
     */
    public function getListeners(EventInterface $event): \Generator
    {
        $eventType = $this->eventTypeService->getEventType($event);
        if (isset($this->mapping[$eventType])) {
            foreach ($this->mapping[$eventType] as $listener) {
                yield new EventListenerContainer($listener, $this->objectManager);
            }
        }
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
        /** @var EventTypeService $reflectionService */
        $eventTypeService = $objectManager->get(EventTypeService::class);
        foreach ($reflectionService->getAllImplementationClassNamesForInterface(EventListenerInterface::class) as $listenerClassName) {
            foreach (get_class_methods($listenerClassName) as $methodName) {
                $eventType = $eventClassName = null;

                preg_match('/^when.*$/', $methodName, $matches);
                if (!isset($matches[0])) {
                    continue;
                }
                $methodName = $matches[0];
                $parameters = array_values($reflectionService->getMethodParameters($listenerClassName, $methodName));

                switch (true) {
                    case count($parameters) === 1:
                        $eventClassName = $parameters[0]['class'];
                        if (!$reflectionService->isClassImplementationOf($eventType, EventInterface::class)) {
                            throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, the first parameter should be cast to an implementation of EventInterface', $listenerClassName, $methodName), 1472504443);
                        }
                        $eventType = $eventTypeService->getEventTypeByImplementation($eventClassName);
                        $metaDataType = null;
                        break;
                    case isset($parameters[1]):
                        $metaDataType = $parameters[1]['class'];
                        if ($metaDataType !== MessageMetadata::class) {
                            throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, the second parameter should be cast to MessageMetaData', $listenerClassName, $methodName), 1472504303);
                        }
                        break;
                }

                if (trim($eventType) === '') {
                    throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, must accept an EventInterface and optionnaly a MessageMetaData', $listenerClassName, $methodName), 1472500228);
                }
                $eventShortName = $eventTypeService->getEventShortTypeByImplementation($eventClassName);
                $expectedMethodName = 'when' . $eventShortName;
                if ($expectedMethodName !== $methodName) {
                    throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, must accept an EventInterface and optionally a MessageMetaData', $listenerClassName, $methodName), 1472500228);
                }

                if (!isset($listeners[$eventType])) {
                    $listeners[$eventType] = [];
                }
                $listeners[$eventType][] = [$listenerClassName, $methodName];
            }
        }

        ksort($listeners);
        return $listeners;
    }
}
