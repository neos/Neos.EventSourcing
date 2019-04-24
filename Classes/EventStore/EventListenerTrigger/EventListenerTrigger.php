<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventStore\EventListenerTrigger;

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
use Neos\EventSourcing\Event\Decorator\EventDecoratorUtilities;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventListener\AppliedEventsLogRepository;
use Neos\EventSourcing\EventListener\EventListenerLocator;
use Neos\Flow\Annotations as Flow;

/**
 * This class is responsible for triggering the correct event listeners (e.g. projectors) for some events which have been published
 * on the event store recently.
 *
 * The published events have to be registered using {@see EventListenerTrigger::enqueueEvents()}, and to invoke the correct
 * event listeners, the method {@see EventListenerTrigger::invoke()} has to be called.
 *
 * @Flow\Scope("singleton")
 */
final class EventListenerTrigger
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
     * @Flow\Inject
     * @var AppliedEventsLogRepository
     */
    protected $appliedEventsLogRepository;

    /**
     * @var array
     */
    private $pendingEventListenerClassNames = [];

    /**
     * Enqueue domain events, such that their corresponding Event Listeners are executed lateron.
     *
     * @param DomainEvents $events
     */
    public function enqueueEvents(DomainEvents $events): void
    {
        foreach ($events as $event) {
            $eventClassName = get_class(EventDecoratorUtilities::extractUndecoratedEvent($event));
            foreach ($this->eventListenerLocator->getListenerClassNamesForEventClassName($eventClassName) as $listenerClassName) {
                $this->pendingEventListenerClassNames[$listenerClassName] = true;
            }
        }
    }

    /**
     * Invoke the event listeners which have been enqueued. NOTE: This usually runs asynchronously by an async job queue.
     */
    public function invoke(): void
    {
        $this->appliedEventsLogRepository->ensureHighestAppliedSequenceNumbersAreInitialized();

        foreach (array_keys($this->pendingEventListenerClassNames) as $listenerClassName) {
            $job = new CatchUpEventListenerJob($listenerClassName);
            // TODO make queue name configurable (per event type?)
            $this->jobManager->queue('neos-eventsourcing', $job);
        }
        $this->pendingEventListenerClassNames = [];
    }

    public function shutdownObject(): void
    {
        $this->invoke();
    }
}
