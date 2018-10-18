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

use Neos\EventSourcing\Event\EventNormalizer;
use Neos\Flow\Annotations as Flow;

/**
 * EventStream
 */
final class EventStream implements \Iterator
{

    /**
     * @Flow\Inject
     * @var EventNormalizer
     */
    protected $eventNormalizer;

    /**
     * @var \Iterator
     */
    private $streamIterator;

    /**
     * @param \Iterator $streamIterator
     */
    public function __construct(\Iterator $streamIterator)
    {
        $this->streamIterator = $streamIterator;
    }

    /**
     * @return EventAndRawEvent
     */
    public function current()
    {
        /** @var RawEvent $rawEvent */
        $rawEvent = $this->streamIterator->current();
        return new EventAndRawEvent(
            $this->eventNormalizer->denormalize($rawEvent->getPayload(), $rawEvent->getType()),
            $rawEvent
        );
    }

    public function next()
    {
        $this->streamIterator->next();
    }

    public function key()
    {
        return $this->streamIterator->key();
    }

    public function valid()
    {
        return $this->streamIterator->valid();
    }

    public function rewind()
    {
        $this->streamIterator->rewind();
    }
}
