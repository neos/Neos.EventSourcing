<?php
namespace Neos\Cqrs\Domain;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Event\EventTransport;
use Neos\Cqrs\EventStore\EventStream;
use Neos\Cqrs\RuntimeException;

/**
 * Base implementation for an event-sourced aggregate root
 */
abstract class AbstractEventSourcedAggregateRoot extends AbstractAggregateRoot implements EventSourcedAggregateRootInterface
{
    /**
     * @param string $identifier
     * @param EventStream $stream
     * @return self
     */
    public static function reconstituteFromEventStream(string $identifier, EventStream $stream)
    {
        $instance = new static($identifier);
        /** @var EventTransport $eventTransport */
        foreach ($stream as $eventTransport) {
            $instance->apply($eventTransport->getEvent());
        }
        return $instance;
    }
}
