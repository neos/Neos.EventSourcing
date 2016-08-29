<?php
namespace Ttree\Cqrs\EventListener;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Event\EventInterface;
use TYPO3\Flow\Annotations as Flow;

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
