<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventListener\Mapping;

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
 * Mapping from multiple Event Class names to their corresponding Event listener, potentially with custom options
 *
 * @Flow\Proxy(false)
 */
class EventToListenerMappings implements \IteratorAggregate, \JsonSerializable
{

    /**
     * @var EventToListenerMapping[] indexed by EventClassName
     */
    private $mappings;

    protected function __construct(array $mappings)
    {
        $this->mappings = $mappings;
    }

    /**
     * @return static
     */
    public static function createEmpty(): static
    {
        return new static([]);
    }

    public function withMapping(EventToListenerMapping $mapping): static
    {
        $mappings = $this->mappings;
        $mappings[] = $mapping;
        return new static($mappings);
    }

    /**
     * @param EventToListenerMapping[] $mappings
     * @return static
     */
    public static function fromArray(array $mappings): static
    {
        foreach ($mappings as $mapping) {
            if (!$mapping instanceof EventToListenerMapping) {
                throw new \InvalidArgumentException(sprintf('Expected array of %s instances, got: %s', EventToListenerMapping::class, \is_object($mapping) ? \get_class($mapping) : \gettype($mapping)), 1578319100);
            }
        }
        return new static(array_values($mappings));
    }

    public function filter(\closure $callback): static
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
     * @return EventToListenerMapping[]|\Iterator<EventToListenerMapping>
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->mappings);
    }

    public function jsonSerialize(): array
    {
        return $this->mappings;
    }
}
