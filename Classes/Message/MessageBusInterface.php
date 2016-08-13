<?php
namespace Flowpack\Cqrs\Message;

/*
 * This file is part of the Flowpack.Cqrs package.
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
