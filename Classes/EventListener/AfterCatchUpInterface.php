<?php
declare(strict_types=1);
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

use Neos\EventSourcing\EventStore\EventEnvelope;

/**
 * This interface can be implemented by Event Listeners / Projectors which want to trigger batched actions after processing
 * a bunch of events. It is useful for performance optimization.
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
 * For implementing this, make sure your projector implements AfterCatchUpInterface, and implement the afterCatchUp() method,
 * which is only triggered at the end of the **current event batch**.
 *
 * This is called from {@see EventListenerInvoker::catchUp()}
 */
interface AfterCatchUpInterface
{
    /**
     * Called directly before completing catchUp(), can be used to trigger batch actions.
     *
     * @return void
     */
    public function afterCatchUp(): void;
}
