<?php
namespace Flowpack\Cqrs\Event;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Message\MessageInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventHandlerLocatorInterface
 */
interface EventHandlerLocatorInterface
{
    /**
     * @param MessageInterface $message
     * @return EventHandlerInterface[]
     */
    public function getHandlers(MessageInterface $message);
}
