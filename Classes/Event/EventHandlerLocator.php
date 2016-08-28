<?php
namespace Ttree\Cqrs\Event;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Annotations\EventHandler;
use Ttree\Cqrs\Event\Exception\EventBusException;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * EventHandlerLocator
 *
 * @Flow\Scope("singleton")
 */
class EventHandlerLocator implements EventHandlerLocatorInterface
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
     * Register event handlers based on annotations
     */
    public function initializeObject()
    {
        $handlers = self::loadHandlers($this->objectManager);
        $this->map = array_merge($handlers, $this->map);
    }

    /**
     * @param string $eventName
     * @param string $handlerName
     * @throws EventBusException
     */
    public function register($eventName, $handlerName)
    {
        if (!$this->objectManager->isRegistered($handlerName)) {
            throw new EventBusException(sprintf(
                "Event handler '%s' is not a registred object", $handlerName
            ));
        }

        $handlerHash = md5($handlerName);
        $this->map[$eventName][$handlerHash] = $handlerName;
    }

    /**
     * @param EventInterface $message
     * @return EventHandlerInterface[]
     */
    public function getHandlers(EventInterface $message)
    {
        $handlers = [];

        $name = EventType::get($message);

        if (!array_key_exists($name, $this->map)) {
            return $handlers;
        }

        foreach ($this->map[$name] as $handlerName) {
            $handlers[] = $this->objectManager->get($handlerName);
        }

        return $handlers;
    }

    /**
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @Flow\CompileStatic
     */
    protected static function loadHandlers(ObjectManagerInterface $objectManager)
    {
        $handlers = [];
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);
        foreach ($reflectionService->getClassNamesByAnnotation(EventHandler::class) as $handler) {
            /** @var EventHandler $annotation */
            $annotation = $reflectionService->getClassAnnotation($handler, EventHandler::class);
            $handlers[$annotation->event][md5($handler)] = $handler;
        }
        return $handlers;
    }
}

