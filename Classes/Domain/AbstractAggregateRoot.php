<?php
namespace Neos\Cqrs\Domain;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Event\AggregateEventInterface;
use Neos\Cqrs\Event\EventInterface;
use Neos\Cqrs\Event\EventTypeResolver;
use TYPO3\Flow\Annotations as Flow;

/**
 * Base class for an aggregate root
 */
abstract class AbstractAggregateRoot implements AggregateRootInterface
{
    /**
     * @var EventTypeResolver
     * @Flow\Inject
     */
    protected $eventTypeService;

    /**
     * Note: This must not be private so it can be set during reconstitution via unserialize()
     *
     * @var string
     */
    protected $identifier;

    /**
     * @var EventInterface[]
     */
    private $events = [];

    /**
     * @param string $identifier
     */
    protected function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    final public function getIdentifier(): string
    {
        return $this->identifier;
    }

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
        if ($event instanceof AggregateEventInterface) {
            $event->setIdentifier($this->getIdentifier());
        }
        $this->apply($event);
        $this->events[] = $event;
    }

    /**
     * Returns the events which have been recorded since the last call of this method.
     *
     * This method is used internally by the persistence layer (for example, the Event Store).
     */
    final public function pullUncommittedEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }

    /**
     * @return bool
     */
    final protected function hasUncommittedEvents(): bool
    {
        return $this->events !== [];
    }

    /**
     * Apply the given event to this aggregate root.
     *
     * @param  EventInterface $event
     * @return void
     */
    final protected function apply(EventInterface $event)
    {
        $method = sprintf('when%s', $this->eventTypeService->getEventShortType($event));
        if (method_exists($this, $method)) {
            $this->$method($event);
        }
    }
}
