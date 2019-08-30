<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventListener\AppliedEventsStorage;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\EventListener\Exception\HighestAppliedSequenceNumberCantBeReservedException;

/**
 * Contract for an authority that tracks the "applied events" state of an Event Listener
 */
interface AppliedEventsStorageInterface
{

    /**
     * Returns the highest applied sequence number and blocks further calls until releaseHighestAppliedSequenceNumber() is invoked
     *
     * Note: Implementations can choose to wait for the lock to be freed (with timeout) or to throw a HighestAppliedSequenceNumberCantBeReservedException if the lock can't be acquired
     *
     * @return int The highest applied event sequence number or -1 if no event has been applied yet
     * @throws HighestAppliedSequenceNumberCantBeReservedException
     */
    public function reserveHighestAppliedEventSequenceNumber(): int;

    /**
     * Releases the lock, @see reserveHighestAppliedEventSequenceNumber
     *
     * @return void
     */
    public function releaseHighestAppliedSequenceNumber(): void;

    /**
     * Updates the highest applied event sequence number
     *
     * @param int $sequenceNumber
     */
    public function saveHighestAppliedSequenceNumber(int $sequenceNumber): void;
}
