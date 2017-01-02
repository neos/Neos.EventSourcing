<?php
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

final class WritableEvents implements \Iterator
{
    /**
     * @var WritableEvent[]
     */
    private $events = [];

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @param WritableEvent $event
     */
    public function append(WritableEvent $event)
    {
        $this->events[] = $event;
    }

    /**
     * @return WritableEvent
     */
    public function current()
    {
        return $this->events[$this->position];
    }

    public function next()
    {
        $this->position ++;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return isset($this->events[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }
}
