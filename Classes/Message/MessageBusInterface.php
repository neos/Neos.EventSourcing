<?php
namespace Ttree\Cqrs\Message;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * MessageBusInterface
 */
interface MessageBusInterface
{
    /**
     * @param MessageInterface $message
     * @return void|MessageResultInterface
     */
    public function handle(MessageInterface $message);
}
