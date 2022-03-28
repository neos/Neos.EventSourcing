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

use Neos\EventSourcing\EventListener\EventListenerInvoker;
use Neos\EventSourcing\EventListener\Exception\HighestAppliedSequenceNumberCantBeReservedException;

/**
 * Contract for an authority that tracks the "applied events" state of an Event Listener. It is used by
 * {@see EventListenerInvoker::catchUp()} to track an event listener's progress, and at the same time ensure that a given
 * event listener does not run concurrently.
 *
 * The contract is to IMPLEMENT A LOCK; so after {@see AppliedEventsStorageInterface::reserveHighestAppliedEventSequenceNumber()}
 * is called by one process, NO OTHER PROCESS (in the same or in a different PHP process) is allowed to enter the following code path,
 * until releaseHighestAppliedSequenceNumber is triggered (in case of an error) or saveHighestAppliedSequenceNumber is triggered (if
 * everything went well).
 *
 * The main use case of this interface is to make the Event Listener / Projector **itself** responsible for storing
 * the currently applied progress, f.e. in another database than the default database or another storage system.
 *
 *
 *
 * ADDITIONAL USE CASE: Trigger BATCHED actions after processing a bunch of events:
 *
 * As an example, let's say you need to update a secondary system after all events have been processed; and we have the
 * following events which were processed in a single {@see EventListenerInvoker::catchUp()} batch:
 *
 * (batch starts)
 * - e1 (group=A)
 * - e2 (group=B)
 * - e3 (group=A)
 * - e4 (group=A)
 * - e5 (group=A)
 * (batch ends)
 *    <--- here, we want to trigger a refresh of group=A and group=B only ONCE, despite having 4 events which touched group=A.
 *
 * NOTE: you cannot control the batching yourself; but this simply depends on what events are committed to the event store
 * at the time you call catchUp().
 *
 * For implementing this, you can directly delegate the implementation back to {@see DoctrineAppliedEventsStorage}, but ADDITIONALLY
 * implement logic in {@see AppliedEventsStorageInterface::saveHighestAppliedSequenceNumber()}, whis is only triggered
 * at the end of the **current event batch**.
 *
 * ```php
 *     public function __construct(DbalClient $client)
 *     {
 *         $this->doctrineAppliedEventsStorage = new DoctrineAppliedEventsStorage(
 *             $client->getConnection(),
 *             get_class($this)
 *         );
 *     }
 *
 *     public function reserveHighestAppliedEventSequenceNumber(): int {
 *         // BEGIN OF BATCH
 *         $this->touchedGroups = []; // here, we store which groups have been touched by this batch
 *         return $this->doctrineAppliedEventsStorage->reserveHighestAppliedEventSequenceNumber();
 *     }
 *
 *     public function whenXY() {
 *         $this->touchedGroups[$event->getGroup()] = true; // adjust to your logic
 *     }
 *
 *     public function releaseHighestAppliedSequenceNumber(): void {
 *          // END OF BATCH - ERROR CASE
 *          $this->doctrineAppliedEventsStorage->releaseHighestAppliedSequenceNumber();
 *     }
 *     public function saveHighestAppliedSequenceNumber(int $sequenceNumber): void {
 *          // END OF BATCH - SUCCESS CASE
 *          $this->doctrineAppliedEventsStorage->saveHighestAppliedSequenceNumber($sequenceNumber);
 *
 *          // here, we can process $this->touchedGroups; e.g. notify a queue etc.
 *     }
 * ```
 *
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
     * Releases the lock, @return void
     * @see reserveHighestAppliedEventSequenceNumber
     *
     */
    public function releaseHighestAppliedSequenceNumber(): void;

    /**
     * Updates the highest applied event sequence number
     *
     * @param int $sequenceNumber
     */
    public function saveHighestAppliedSequenceNumber(int $sequenceNumber): void;
}
