<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventStore;

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
final class WritableEvents implements \IteratorAggregate, \Countable
{

    /**
     * @var WritableEvent[]
     */
    private $events;

    private function __construct(array $events)
    {
        $this->events = $events;
    }

    public static function fromArray(array $events): self
    {
        foreach ($events as $event) {
            if (!$event instanceof WritableEvent) {
                throw new \InvalidArgumentException(sprintf('Only instances of WritableEvent are allowed, given: %s', \is_object($event) ? \get_class($event) : \gettype($event)), 1540316594);
            }
        }
        return new self(array_values($events));
    }

    public function append(WritableEvent $event): self
    {
        $events = $this->events;
        $events[] = $event;
        return new self($events);
    }

    /**
     * @return WritableEvent[]|\ArrayIterator<WritableEvent>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->events);
    }

    public function count(): int
    {
        return \count($this->events);
    }
}
