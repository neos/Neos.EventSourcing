<?php
namespace Neos\EventSourcing\Projection\Doctrine;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\EventListener\AppliedEventsLogRepository;
use Neos\EventSourcing\EventListener\AsynchronousEventListenerInterface;
use Neos\Flow\Annotations as Flow;

/**
 * A base class for Doctrine-based projectors
 *
 * @api
 */
abstract class AbstractAsynchronousDoctrineProjector extends AbstractDoctrineProjector implements AsynchronousEventListenerInterface
{
    /**
     * @var AppliedEventsLogRepository
     */
    private $appliedEventsLogRepository;

    /**
     * @param AppliedEventsLogRepository $appliedEventsLogRepository
     * @return void
     */
    public function injectAppliedEventsLogRepository(AppliedEventsLogRepository $appliedEventsLogRepository)
    {
        $this->appliedEventsLogRepository = $appliedEventsLogRepository;
    }

    /**
     * Returns the last seen sequence number of events which has been applied to the concrete event listener.
     *
     * @return int
     */
    public function getHighestAppliedSequenceNumber(): int
    {
        return $this->appliedEventsLogRepository->getHighestAppliedSequenceNumber(get_class($this));
    }

    /**
     * Saves the $sequenceNumber as the last seen sequence number of events which have been applied to the concrete
     * event listener.
     *
     * @param int $sequenceNumber
     * @return void
     */
    public function saveHighestAppliedSequenceNumber(int $sequenceNumber)
    {
        $this->appliedEventsLogRepository->saveHighestAppliedSequenceNumber(get_class($this), $sequenceNumber);
    }
}
