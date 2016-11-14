<?php
namespace Neos\Cqrs\EventListener;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * AppliedEventsAwareEventListener
 *
 * Contract for event listeners which are aware of and can store the information which events have already been applied.
 */
interface AppliedEventsAwareEventListener extends EventListenerInterface
{
    /**
     * Returns the last seen sequence number of events which has been applied to the concrete event listener.
     *
     * @return int
     */
    public function getHighestAppliedSequenceNumber(): int;

    /**
     * Saves the $sequenceNumber as the last seen sequence number of events which have been applied to the concrete
     * event listener.
     *
     * @param int $sequenceNumber
     * @return void
     */
    public function saveSequenceNumber(int $sequenceNumber);
}
