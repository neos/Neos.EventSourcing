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
 * Marker interface for asynchronous event listeners
 *
 * Asynchronous event listeners are not triggered directly when new events are published, but only when executing the CLI command projection:catchup
 */
interface AsynchronousEventListenerInterface extends EventListenerInterface
{
}
