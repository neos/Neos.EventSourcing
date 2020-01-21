<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventPublisher;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\DomainEvents;

/**
 * Contract for an Event Publisher that is invoked by the Event Store when ever new events where committed
 *
 * The task of an Event Publisher is to inform 3rd parties whenever new events have been written to the Event Store.
 * Usually the recipients are Projectors or other Event Listeners that then apply those Events to update their state and/or react otherwise.
 */
interface EventPublisherInterface
{
    public function publish(DomainEvents $events): void;
}
