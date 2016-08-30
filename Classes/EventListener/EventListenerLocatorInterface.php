<?php
namespace Ttree\Cqrs\EventListener;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Ttree\Cqrs\Event\EventInterface;

/**
 * EventListenerLocatorInterface
 */
interface EventListenerLocatorInterface
{
    /**
     * @param EventInterface $message
     * @return EventListenerInterface[]
     */
    public function getListeners(EventInterface $message);
}
