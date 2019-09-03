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

use Neos\EventSourcing\EventListener\AppliedEventsStorage\AppliedEventsStorageInterface;

/**
 * A contract for Event Listeners that provide their own AppliedEventsStorageInterface implementation
 *
 * This can be used in order to track the highest applied event sequence number using a different mechanism
 * than the default, doctrine based, one.
 * @see AppliedEventsStorageInterface
 */
interface ProvidesAppliedEventsStorageInterface
{
    public function getAppliedEventsStorage(): AppliedEventsStorageInterface;
}
