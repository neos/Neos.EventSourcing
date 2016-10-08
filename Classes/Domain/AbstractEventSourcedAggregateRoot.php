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

use Neos\Cqrs\Event\AggregateEventInterface;
use Neos\Cqrs\Event\EventTransport;
use Neos\Cqrs\EventStore\EventStream;
use Neos\Cqrs\RuntimeException;

/**
 * AggregateRootTrait
 */
abstract class AbstractEventSourcedAggregateRoot extends AbstractAggregateRoot implements EventSourcedAggregateRootInterface
{
    /**
     * @param EventStream $stream
     * @throws RuntimeException
     */
    public function reconstituteFromEventStream(EventStream $stream)
    {
        if ($this->getEvents() !== []) {
            throw new RuntimeException(sprintf('%s has already been reconstituted from the event stream.', get_class($this)), 1474547708762);
        }

        /** @var EventTransport $eventTransport */
        foreach ($stream as $eventTransport) {
            $event = $eventTransport->getEvent();
            if ($event instanceof AggregateEventInterface) {
                $this->setAggregateIdentifier($event->getAggregateIdentifier());
            }
            $this->apply($event);
        }
    }
}
