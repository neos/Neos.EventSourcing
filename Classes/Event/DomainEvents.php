<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Event;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A set of Domain Events
 *
 * @Flow\Proxy(false)
 * @implements \IteratorAggregate<int,DomainEventInterface>
 */
final class DomainEvents implements \IteratorAggregate, \Countable
{
    /**
     * @var array<int,DomainEventInterface>
     */
    private array $events;

    /**
     * @var \ArrayIterator<int,DomainEventInterface>
     */
    private \ArrayIterator $iterator;

    /**
     * @param array<int,DomainEventInterface> $events
     */
    private function __construct(array $events)
    {
        $this->events = $events;
        $this->iterator = new \ArrayIterator($events);
    }

    public static function createEmpty(): DomainEvents
    {
        return new DomainEvents([]);
    }

    /**
     * @param array<string|int,DomainEventInterface> $events
     */
    public static function fromArray(array $events): DomainEvents
    {
        foreach ($events as $event) {
            if (!$event instanceof DomainEventInterface) {
                throw new \InvalidArgumentException(sprintf('Only instances of EventInterface are allowed, given: %s', \is_object($event) ? \get_class($event) : \gettype($event)), 1540311882);
            }
        }
        return new DomainEvents(array_values($events));
    }

    public static function withSingleEvent(DomainEventInterface $event): DomainEvents
    {
        return new DomainEvents([$event]);
    }

    public function appendEvent(DomainEventInterface $event): DomainEvents
    {
        $events = $this->events;
        $events[] = $event;

        return new DomainEvents($events);
    }

    public function appendEvents(DomainEvents $other): DomainEvents
    {
        $events = array_merge($this->events, $other->events);

        return new DomainEvents($events);
    }

    public function getFirst(): DomainEventInterface
    {
        if ($this->isEmpty()) {
            throw new \RuntimeException('Cant\'t return first event of an empty DomainEvents', 1540909869);
        }

        return $this->events[0];
    }

    /**
     * @return DomainEventInterface[]|\ArrayIterator<int,DomainEventInterface>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function map(\Closure $processor): DomainEvents
    {
        $convertedEvents = array_map($processor, $this->events);
        return DomainEvents::fromArray($convertedEvents);
    }

    public function filter(\Closure $expression): DomainEvents
    {
        $filteredEvents = array_filter($this->events, $expression);
        return DomainEvents::fromArray($filteredEvents);
    }

    public function isEmpty(): bool
    {
        return $this->events === [];
    }

    public function count(): int
    {
        return \count($this->events);
    }
}
