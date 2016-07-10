<?php
namespace Flowpack\Cqrs\Event;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Event\Exception\EventBusException;
use Flowpack\Cqrs\Message\MessageInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;

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
     * @param string $eventName
     * @param string $handlerName
     * @throws EventBusException
     */
    public function register($eventName, $handlerName)
    {
        if (!$this->objectManager->isRegistered($handlerName)) {
            throw new EventBusException(sprintf(
                "Event handler '%s' cannot be found by locator", $handlerName
            ));
        }

        $this->map[$eventName][] = $handlerName;
    }

    /**
     * @param MessageInterface $message
     * @return EventHandlerInterface[]
     */
    public function getHandlers(MessageInterface $message)
    {
        $handlers = [];

        $eventName = $message->getName();

        if (!array_key_exists($eventName, $this->map)) {
            return $handlers;
        }

        foreach ($this->map[$eventName] as $handlerName) {
            $handlers[] = $this->objectManager->get($handlerName);
        }

        return $handlers;
    }
}

