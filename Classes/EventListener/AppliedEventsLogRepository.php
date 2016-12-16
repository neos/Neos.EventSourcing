<?php
namespace Neos\EventSourcing\EventListener;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager as EntityManager;
use Neos\Flow\Annotations as Flow;

/**
 * A generic Doctrine-based repository for applied events logs.
 *
 * This repository can be used by projectors, process managers or other asynchronous event listeners for keeping
 * track of the highest sequence number of the applied events. This information is used and updated when catching up
 * on new events.
 *
 * Alternatively to using this repository, event listeners are free to implement their own way of storing this
 * information.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class AppliedEventsLogRepository
{
    /**
     * @Flow\Inject
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Returns the last seen sequence number of events which has been applied to the concrete event listener.
     *
     * @param string $eventListenerClassName
     * @return int
     */
    public function getHighestAppliedSequenceNumber(string $eventListenerClassName): int
    {
        $eventListenerIdentifier = $this->renderEventListenerIdentifier($eventListenerClassName);
        $appliedEventsLog = $this->entityManager->find(AppliedEventsLog::class, $eventListenerIdentifier);
        return ($appliedEventsLog instanceof AppliedEventsLog ? $appliedEventsLog->highestAppliedSequenceNumber : 0);
    }

    /**
     * Saves the $sequenceNumber as the last seen sequence number of events which have been applied to the concrete
     * event listener.
     *
     * @param string $eventListenerClassName
     * @param int $sequenceNumber
     * @return void
     */
    public function saveHighestAppliedSequenceNumber(string $eventListenerClassName, int $sequenceNumber)
    {
        $eventListenerIdentifier = $this->renderEventListenerIdentifier($eventListenerClassName);
        $appliedEventsLog = $this->entityManager->find(AppliedEventsLog::class, $eventListenerIdentifier);
        if ($appliedEventsLog === null) {
            $appliedEventsLog = new AppliedEventsLog();
            $appliedEventsLog->eventListenerIdentifier= $eventListenerIdentifier;
        }
        $appliedEventsLog->highestAppliedSequenceNumber = $sequenceNumber;
        $this->entityManager->persist($appliedEventsLog);
    }

    /**
     * Renders a event listener identifier which can be used as an id in the applied events log
     *
     * @param string $eventListenerClassName
     * @return string
     */
    private function renderEventListenerIdentifier(string $eventListenerClassName): string
    {
        $identifier = strtolower(str_replace('\\', '_', $eventListenerClassName));
        if (strlen($identifier) > 255) {
            $identifier = substr($identifier, 0, 255 - 6) . '_' . substr(sha1($identifier), 0, 5);
        }
        return $identifier;
    }
}
