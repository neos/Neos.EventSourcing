<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventBus;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\JobQueue\Common\Job\JobManager;
use Neos\EventSourcing\Event\Decorator\DomainEventWithMetadataInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventListener\EventListenerLocator;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class EventBus
{

    /**
     * @Flow\Inject
     * @var JobManager
     */
    protected $jobManager;

    /**
     * @Flow\Inject
     * @var EventListenerLocator
     */
    protected $eventListenerLocator;

    /**
     * @var array
     */
    private $pendingEventListenerClassNames = [];

    public function publish(DomainEvents $events): void
    {
        foreach ($events as $event) {
            $eventClassName = $event instanceof DomainEventWithMetadataInterface ? get_class($event->getEvent()) : get_class($event);
            foreach ($this->eventListenerLocator->getListenerClassNamesForEventClassName($eventClassName) as $listenerClassName) {
                $this->pendingEventListenerClassNames[$listenerClassName] = true;
            }
        }
    }

    public function flush(): void
    {
        foreach (array_keys($this->pendingEventListenerClassNames) as $listenerClassName) {
            $job = new CatchUpEventListenerJob($listenerClassName);
            // TODO make queue name configurable (per event type?)
            $this->jobManager->queue('neos-eventsourcing', $job);
        }
        $this->pendingEventListenerClassNames = [];
    }

    public function shutdownObject(): void
    {
        $this->flush();
    }

}
