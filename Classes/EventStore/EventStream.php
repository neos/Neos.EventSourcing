<?php
namespace Neos\Cqrs\EventStore;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Event\EventInterface;
use Neos\Cqrs\Event\EventTransport;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventStream
 */
class EventStream implements \IteratorAggregate
{
    /**
     * @var EventInterface[] All AR events
     */
    protected $events = [];

    /**
     * @var array New AR events, since AR reconstituted from stream
     */
    protected $new = [];

    /**
     * @var integer
     */
    protected $version;

    /**
     * @param EventInterface[] $events
     * @param integer $version
     */
    public function __construct(array $events = [], int $version = 0)
    {
        $this->events = $events;
        $this->version = $version;
    }

    /**
     * @return array
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
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param EventTransport $event
     */
    public function addEvent(EventTransport $event)
    {
        $this->events[] = $event;
        $this->new[] = $event;
    }

    /**
     * @param EventTransport[] $events
     */
    public function addEvents(...$events)
    {
        foreach ($events as $event) {
            $this->addEvent($event);
        }
    }

    /**
     * @param integer|null $version
     */
    public function markAllApplied($version = null)
    {
        $this->version = $version;
        $this->new = [];
    }

    /**
     * Retrieve an external iterator
     * @return \Generator
     */
    public function getIterator()
    {
        foreach ($this->events as $event) {
            yield $event;
        }
    }
}
