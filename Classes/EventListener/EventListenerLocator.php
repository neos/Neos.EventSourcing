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
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @throws Exception
     * @Flow\CompileStatic
     */
    public static function detectListeners(ObjectManagerInterface $objectManager)
    {
        $listeners = [];
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);
        foreach ($reflectionService->getAllImplementationClassNamesForInterface(EventListenerInterface::class) as $listener) {
            foreach (get_class_methods($listener) as $method) {
                preg_match('/^when.*$/', $method, $matches);
                if (!isset($matches[0])) {
                    continue;
                }
                $method = $matches[0];
                $parameters = array_values($reflectionService->getMethodParameters($listener, $method));
                $eventType = null;
                switch (true) {
                    case count($parameters) === 1:
                        $eventType = $parameters[0]['class'];
                        if (!$reflectionService->isClassImplementationOf($eventType, EventInterface::class)) {
                            throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, the first parameter should by casted to an implementation of EventInterface', $listener, $method), 1472504443);
                        }
                        $metaDataType = null;
                        break;
                    case count($parameters) > 1:
                        $eventType = $parameters[0]['class'];
                        $metaDataType = $parameters[1]['class'];
                        if ($metaDataType !== MessageMetadata::class) {
                            throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, the second parameter should by casted to MessageMetaData', $listener, $method), 1472504303);
                        }
                        break;
                }
                if (trim($eventType) === '') {
                    throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, must accept an EventInterface and optionnaly a MessageMetaData', $listener, $method), 1472500228);
                }
                $eventTypeParts = explode('\\', $eventType);
                $expectedMethod = 'when' . end($eventTypeParts);
                if ($expectedMethod !== $method) {
                    throw new Exception(sprintf('Invalid listener in %s::%s the method name is wrong, must be "%s"', $listener, $method, $expectedMethod), 1472500228);
                }
                if (!isset($listeners[$eventType])) {
                    $listeners[$eventType] = [];
                }
                $listeners[$eventType][] = [$listener, $method];
            }
        }
        ksort($listeners);
        return $listeners;
    }
}
