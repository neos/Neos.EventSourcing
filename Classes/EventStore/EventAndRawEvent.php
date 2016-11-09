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
 * It contains the original event data including raw payload, metadata and technical meta information
 * and the converted EventInterface instance
 */
final class EventAndRawEvent
{
    /**
     * @var EventInterface
     */
    private $event;

    /**
     * @var RawEvent
     */
    private $rawEvent;

    public function __construct(EventInterface $event, RawEvent $rawEvent)
    {
        $this->event = $event;
        $this->rawEvent = $rawEvent;
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

    /**
     * The raw event including version and metadata, as stored in the Event Store
     *
     * @return RawEvent
     */
    public function getRawEvent(): RawEvent
    {
        return $this->rawEvent;
    }
}
