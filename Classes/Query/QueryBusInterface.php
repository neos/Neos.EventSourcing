<?php
namespace Ttree\Cqrs\Query;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Message\MessageResultInterface;

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
