<?php
namespace Flowpack\Cqrs\EventStore;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Domain\Uuid;
use Flowpack\Cqrs\Event\EventInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventStream
 */
class EventStream implements \IteratorAggregate
{
    /** @var Uuid */
    protected $aggregateId;

    /** @var string */
    protected $aggregateName;

    /** @var EventInterface[] All AR events */
    protected $events = [];

    /** @var array New AR events, since AR reconstituted from stream */
    protected $new = [];

    /** @var int */
    protected $version;

    /**
     * EventStream constructor
     * @param Uuid $aggregateId
     * @param $aggregateName
     * @param EventInterface[] $events
     * @param int $version
     */
    public function __construct(Uuid $aggregateId, $aggregateName, array $events = [], $version = 1)
    {
        $this->aggregateId = $aggregateId;
        $this->aggregateName = $aggregateName;
        $this->events = $events;
        $this->version = $version;
    }

    /**
     * @return Uuid
     */
    public function getAggregateId()
    {
        return $this->aggregateId;
    }

    /**
     * @return string
     */
    public function getAggregateName()
    {
        return $this->aggregateName;
    }

    /**
     * @return EventInterface[]
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @return EventInterface[]
     */
    public function getNewEvents()
    {
        return $this->new;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param EventInterface $event
     */
    public function addEvent(EventInterface $event)
    {
        $this->events[] = $event;
        $this->new[] = $event;
    }

    /**
     * @param EventInterface[] $events
     */
    public function addEvents(array $events)
    {
        foreach ($events as $event) {
            $this->addEvent($event);
        }
    }

    /**
     * @param int|null $version
     */
    public function markAllApplied($version = null)
    {
        $this->version = $version;
        $this->new = [];
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->events);
    }
}
