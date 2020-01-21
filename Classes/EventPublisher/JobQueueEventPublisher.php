<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventPublisher;

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
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventPublisher\JobQueue\CatchUpEventListenerJob;
use Neos\EventSourcing\EventListener\Mapping\EventToListenerMappings;
use Neos\Flow\Annotations as Flow;

/**
 * An Event Publisher that sends events to a Job Queue using the Flowpack.JobQueue package.
 *
 * The queue Name
 */
final class JobQueueEventPublisher implements EventPublisherInterface
{
    private const DEFAULT_QUEUE_NAME = 'neos-eventsourcing';

    /**
     * @Flow\Inject
     * @var JobManager
     */
    protected $jobManager;

    /**
     * @var string
     */
    private $eventStoreIdentifier;

    /**
     * @var EventToListenerMappings
     */
    private $mappings;

    public function __construct(string $eventStoreIdentifier, EventToListenerMappings $mappings)
    {
        $this->eventStoreIdentifier = $eventStoreIdentifier;
        $this->mappings = $mappings;
    }

    /**
     * Enqueue domain events, such that their corresponding Event Listeners are executed lateron.
     *
     * @param DomainEvents $events
     */
    public function publish(DomainEvents $events): void
    {
        $queuedEventListenerClassNames = [];
        foreach ($this->mappings->getMappingsForEvents($events) as $mapping) {
            $listenerClassName = $mapping->getListenerClassName();
            if (isset($queuedEventListenerClassNames[$listenerClassName])) {
                continue;
            }
            $queueName = $mapping->getOption('queueName', self::DEFAULT_QUEUE_NAME);
            $options = $mapping->getOption('queueOptions', []);
            $this->jobManager->queue($queueName, new CatchUpEventListenerJob($listenerClassName, $this->eventStoreIdentifier), $options);
            $queuedEventListenerClassNames[$listenerClassName] = true;
        }
    }
}
