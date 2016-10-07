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
use Neos\Cqrs\Event\EventTransport;
use Neos\Cqrs\Event\EventTypeService;
use Neos\Cqrs\Message\MessageMetadata;
use TYPO3\Flow\Annotations as Flow;

/**
 * Base class for an aggregate root
 */
abstract class AbstractAggregateRoot implements AggregateRootInterface
{
    /**
     * @var EventTypeService
     * @Flow\Inject
     */
    protected $eventTypeService;

    /**
     * @var string
     */
    protected $aggregateIdentifier;

    /**
     * @var string
     */
    protected $aggregateName;

    /**
     * @var EventTransport[]
     */
    protected $events = [];

    /**
     * Contains a list of events which have been recorded while the aggregate hasn't been initialized yet
     * @var array
     */
    protected $pendingEvents = [];

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * Record pending events recorded before the object initialization
     */
    public function initializeObject()
    {
        $this->initialized = true;
        foreach ($this->pendingEvents as $data) {
            list($event, $metadata) = $data;
            $this->recordThat($event, $metadata);
        }
    }

    /**
     * @param string $identifier
     * @return void
     */
    protected function setAggregateIdentifier($identifier)
    {
        $this->aggregateIdentifier = $identifier;
    }

    /**
     * @return string
     */
    public function getAggregateIdentifier(): string
    {
        return $this->aggregateIdentifier;
    }

    /**
     * Apply an event to the current aggregate root
     *
     * If the event aggregate identifier and name is not set the event
     * is automatically set with the current aggregate identifier
     * and name.
     *
     * @param AggregateEventInterface $event
     * @param array $metadata
     * @api
     */
    public function recordThat(AggregateEventInterface $event, array $metadata = [])
    {
        if ($this->initialized === false) {
            // Queue event before object initialization
            $this->pendingEvents[] = [$event, $metadata];
            return;
        }
        $event->setAggregateIdentifier($this->getAggregateIdentifier());

        $messageMetadata = new MessageMetadata($metadata);

        $this->apply($event);

        $this->events[] = new EventTransport($event, $messageMetadata);
    }

    /**
     * Returns the events which have been recorded since the last call of this method.
     *
     * This method is used internally by the persistence layer (for example, the Event Store).
     *
     * @return array
     */
    public function pullUncommittedEvents(): array
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
    protected function apply(EventInterface $event)
    {
        $method = sprintf('when%s', $this->eventTypeService->getEventShortType($event));
        if (method_exists($this, $method)) {
            $this->$method($event);
        }
    }
}
