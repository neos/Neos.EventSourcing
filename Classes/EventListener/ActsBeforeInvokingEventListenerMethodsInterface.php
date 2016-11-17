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
 * ActsBeforeInvokingEventListenerMethodsInterface
 *
 * If this interface is implemented for an Event Listener class, the Event Publisher will call the respective
 * before method before invoking the actual event listener method (whenSomethingHappened()).
 */
interface ActsBeforeInvokingEventListenerMethodsInterface extends EventListenerInterface
{
    /**
     * Called before a listener method is invoked
     *
     * @param EventInterface $event The event to be dispatched
     * @param RawEvent $rawEvent The raw event to be dispatched
     * @return void
     */
    public function beforeInvokingEventListenerMethod(EventInterface $event, RawEvent $rawEvent);
}
