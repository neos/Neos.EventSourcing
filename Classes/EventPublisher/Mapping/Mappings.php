<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventPublisher\Mapping;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\Flow\Annotations as Flow;

/**
 * Mapping from multiple Event Class names to their corresponding Event listener, potentially with custom options
 *
 * @Flow\Proxy(false)
 */
class Mappings implements \IteratorAggregate, \JsonSerializable
{

    /**
     * @var Mapping[] indexed by EventClassName
     */
    private $mappings;

    protected function __construct(array $mappings)
    {
        $this->mappings = $mappings;
    }

    /**
     * @return static
     */
    public static function create(): self
    {
        return new static([]);
    }

    /**
     * @param Mapping[] $mappings
     * @return static
     */
    public static function fromArray(array $mappings): self
    {
        foreach ($mappings as $mapping) {
            if (!$mapping instanceof Mapping) {
                throw new \InvalidArgumentException(sprintf('Expected array of %s instances, got: %s', Mapping::class, is_object($mapping) ? get_class($mapping) : gettype($mapping)), 1578319100);
            }
        }
        return new static(array_values($mappings));
    }

    public function getMappingsForEvents(DomainEvents $events): Mappings
    {
        $matchingMappings = [];
        foreach ($events as $event) {
            $eventClassName = \get_class($event instanceof DecoratedEvent ? $event->getWrappedEvent() : $event);
            if (isset($matchingMappings[$eventClassName])) {
                continue;
            }
            $matchingMappings[$eventClassName] = array_filter($this->mappings, static function(Mapping $mapping) use ($eventClassName) {
                return $mapping->getEventClassName() === $eventClassName;
            });
        }
        return new static(array_merge(...array_values($matchingMappings)));
    }

    public function filter(\closure $callback): Mappings
    {
        return new static(array_filter($this->mappings, $callback));
    }

    public function hasMappingForListenerClassName(string $listenerClassName): bool
    {
        foreach ($this->mappings as $mapping) {
            if ($mapping->getListenerClassName() === $listenerClassName) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Mapping[]|\Iterator<Mapping>
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->mappings);
    }

    public function jsonSerialize()
    {
        return $this->mappings;
    }
}
