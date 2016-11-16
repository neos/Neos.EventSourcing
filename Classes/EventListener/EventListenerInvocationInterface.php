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
use Neos\Cqrs\EventStore\RawEvent;

/**
 * EventListenerInvocationInterface
 *
 * If this interface is implemented for an Event Listener class, the Event Publisher will call only the
 * `invokeEventListenerMethod()` method and never the specific `when*()` event listener methods.
 */
interface EventListenerInvocationInterface extends EventListenerInterface
{
    /**
     * Manually invoke an event listener method
     *
     * @param string $eventListenerMethodName Name of the method the Event Publisher would call
     * @param EventInterface $event The event to be dispatched
     * @param RawEvent $rawEvent The raw event to be dispatched
     * @return void
     */
    public function invokeEventListenerMethod(string $eventListenerMethodName, EventInterface $event, RawEvent $rawEvent);
}
