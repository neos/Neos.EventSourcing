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
 * If an Event Listener implements this interface, the afterInvoke() method is called
 * after the actual event listener method when<SomethingHappened>() was invoked.
 */
interface AfterInvokeInterface
{
    /**
     * Called after a listener method is invoked
     *
     * @param EventEnvelope $eventEnvelope
     * @return void
     */
    public function afterInvoke(EventEnvelope $eventEnvelope): void;
}
