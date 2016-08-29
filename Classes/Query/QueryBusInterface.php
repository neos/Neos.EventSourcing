<?php
namespace Ttree\Cqrs\Query;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Message\MessageBusInterface;
use Ttree\Cqrs\Message\MessageResultInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * QueryBusInterface
 */
interface QueryBusInterface
{
    /**
     * @param QueryInterface $message
     * @return MessageResultInterface
     */
    public function handle(QueryInterface $message);
}
