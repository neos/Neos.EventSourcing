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
 * @Flow\Proxy(false)
 */
final class DomainEvents implements \IteratorAggregate, \Countable
{

    /**
     * @var DomainEventInterface[]
     */
    private $events;

    private function __construct(array $events)
    {
        $this->events = $events;
    }

    public static function createEmpty(): self
    {
        return new static([]);
    }

    public static function fromArray(array $events): self
    {
        foreach ($events as $event) {
            if (!$event instanceof DomainEventInterface) {
                throw new \InvalidArgumentException(sprintf('Only instances of EventInterface are allowed, given: %s', is_object($event) ? get_class($event) : gettype($event)), 1540311882);
            }
        }
        return new static(array_values($events));
    }

    public static function withSingleEvent(DomainEventInterface $event): self
    {
        return new static([$event]);
    }

    public function appendEvent(DomainEventInterface $event): self
    {
        $events = $this->events;
        $events[] = $event;
        return new static($events);
    }

    public function appendEvents(DomainEvents $other): self
    {
        $events = array_merge($this->events, $other->events);
        return new static($events);
    }

    public function getFirst(): DomainEventInterface
    {
        if ($this->isEmpty()) {
            throw new \RuntimeException('Cant\'t return first event of an empty DomainEvents', 1540909869);
        }
        return $this->events[0];
    }

    /**
     * @return DomainEventInterface[]|\ArrayIterator<DomainEventInterface>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->events);
    }

    public function map(\Closure $processor): self
    {
        $convertedEvents = array_map($processor, $this->events);
        return self::fromArray($convertedEvents);
    }

    public function filter(\Closure $expression): self
    {
        $filteredEvents = array_filter($this->events, $expression);
        return self::fromArray($filteredEvents);
    }

    public function isEmpty(): bool
    {
        return $this->events === [];
    }

    public function count(): int
    {
        return count($this->events);
    }
}
