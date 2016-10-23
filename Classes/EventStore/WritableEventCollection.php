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

final class WritableEventCollection implements WritableToStreamInterface
{
    /**
     * @var WritableEvent[]
     */
    private $events = [];

    /**
     * @param array $events
     */
    public function __construct(array $events)
    {
        $this->validateEvents($events);
        $this->events = $events;
    }

    /**
     * @return array
     */
    public function toStreamData()
    {
        return array_map(function (WritableEvent $event) {
            return $event->toStreamData();
        }, $this->events);
    }

    /**
     * @param array $events
     */
    private function validateEvents(array $events)
    {
        foreach ($events as $event) {
            if (!$event instanceof WritableEvent) {
                throw new \InvalidArgumentException();
            }
        }
    }
}
