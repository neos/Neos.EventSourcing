<?php
namespace Neos\EventSourcing\Domain;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\EventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Base class for an aggregate root
 */
abstract class AbstractAggregateRoot implements AggregateRootInterface
{
    /**
     * @var EventInterface[]
     */
    private $events = [];

    /**
     * Apply an event to the current aggregate root
     *
     * If the event is an instance of AggregateEventInterface, the aggregate identifier will be set
     *
     * @param EventInterface $event
     * @api
     */
    final public function recordThat(EventInterface $event)
    {
        $this->apply($event);
        $this->events[] = $event;
    }

    /**
     * Returns the events which have been recorded since the last call of this method.
     *
     * This method is used internally by the persistence layer (for example, the Event Store).
     *
     * @return EventInterface[]
     */
    final public function pullUncommittedEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }

    /**
     * Apply the given event to this aggregate root.
     *
     * @param  EventInterface $event
     * @return void
     */
    final protected function apply(EventInterface $event)
    {
        $methodName = sprintf('when%s', (new \ReflectionClass($event))->getShortName());
        if (method_exists($this, $methodName)) {
            $this->$methodName($event);
        }
    }
}
