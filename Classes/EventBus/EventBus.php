<?php
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
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\EventListener\EventListenerLocator;
use Neos\Flow\Annotations as Flow;

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

    public function dispatch(DomainEventInterface $domainEvent): void
    {
        $eventClassName = $domainEvent instanceof DomainEventWithMetadataInterface ? get_class($domainEvent->getEvent()) : get_class($domainEvent);
        foreach ($this->eventListenerLocator->getListenerClassNamesForEventClassName($eventClassName) as $listenerClassName) {
            $job = new CatchUpEventListenerJob($listenerClassName);
            // TODO make queue name configurable (per event type?)
            $this->jobManager->queue('neos-eventsourcing', $job);
        }
    }

}
