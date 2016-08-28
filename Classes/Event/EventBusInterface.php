<?php
namespace Ttree\Cqrs\Event;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Message\MessageBusInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventBusInterface
 */
interface EventBusInterface
{
    /**
     * @param EventInterface $message
     * @return void
     */
    public function handle(EventInterface $message);
}
