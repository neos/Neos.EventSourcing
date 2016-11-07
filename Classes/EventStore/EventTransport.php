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

/**
 * Wrapper for a event that was loaded from the Event Store.
 * It contains the raw event data including version & metadata (storedEvent)
 * and the converted EventInterface instance (event)
 */
final class EventTransport
{
    /**
     * @var StoredEvent
     */
    private $storedEvent;

    /**
     * @var EventInterface
     */
    private $event;

    public function __construct(StoredEvent $storedEvent, EventInterface $event)
    {
        $this->storedEvent = $storedEvent;
        $this->event = $event;
    }

    /**
     * The raw event including version and metadata, as stored in the Event Store
     *
     * @return StoredEvent
     */
    public function getStoredEvent(): StoredEvent
    {
        return $this->storedEvent;
    }

    /**
     * The converted event instance
     *
     * @return EventInterface
     */
    public function getEvent(): EventInterface
    {
        return $this->event;
    }
}
