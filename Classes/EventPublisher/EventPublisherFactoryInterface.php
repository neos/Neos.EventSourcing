<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventPublisher;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Contract for an Event Publisher factory that builds Event Publisher instances for a given Event Store identifier
 */
interface EventPublisherFactoryInterface
{
    /**
     * Retrieves/builds an EventStore instance for the given EventStore identifier
     *
     * @param string $eventStoreIdentifier The unique Event Store identifier as configured
     * @return EventPublisherInterface
     */
    public function create(string $eventStoreIdentifier): EventPublisherInterface;
}
