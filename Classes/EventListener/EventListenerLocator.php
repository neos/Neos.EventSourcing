<?php
namespace Ttree\Cqrs\EventListener;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Event\EventInterface;
use Ttree\Cqrs\Event\EventTransport;
use Ttree\Cqrs\Event\EventType;
use Ttree\Cqrs\Exception;
use Ttree\Cqrs\Message\MessageMetadata;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * EventListenerLocator
 *
 * @Flow\Scope("singleton")
 */
class EventListenerLocator implements EventListenerLocatorInterface
{
    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $map = [];

    /**
     * Register event listeners based on annotations
     */
    public function initializeObject()
    {
        $this->map = self::detectListeners($this->objectManager);
    }

    /**
     * @param EventInterface $message
     * @return EventListenerInterface[]
     */
    public function getListeners(EventInterface $message)
    {
        $eventType = EventType::get($message);
        if (!isset($this->map[$eventType])) {
            return [];
        }
        return array_map(function ($listener) {
            return function (EventTransport $eventTransport) use ($listener) {
                list($class, $method) = $listener;
                $handler = $this->objectManager->get($class);
                $handler->$method($eventTransport->getEvent(), $eventTransport->getMetaData());
            };
        }, $this->map[$eventType]);
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
                preg_match('/^on[AB]?.*$/', $method, $matches);
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
                    throw new Exception(sprintf('Invalid listener in %s::%s the method signature is wrong, must accent an EventInterface and optionnaly a MessageMetaData', $listener, $method), 1472500228);
                }
                $eventTypeParts = explode('\\', $eventType);
                $expectedMethod = 'on' . end($eventTypeParts);
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

