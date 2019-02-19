<?php
declare(strict_types=1);
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

/**
 * ActsAfterInvokingEventListenerMethodsInterface
 *
 * If this interface is implemented for an Event Listener class, the Event Publisher will call the respective
 * method after invoking the actual event listener method (whenSomethingHappened()).
 */
interface ActsAfterInvokingEventListenerMethodsInterface extends EventListenerInterface
{
    /**
     * Called before a listener method is invoked
     *
     * @param DomainEventInterface $event The event to be dispatched
     * @param RawEvent $rawEvent The raw event to be dispatched
     * @return void
     */
    public function afterInvokingEventListenerMethod(DomainEventInterface $event, RawEvent $rawEvent): void;
}
