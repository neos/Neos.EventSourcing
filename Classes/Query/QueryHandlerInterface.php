<?php
namespace Ttree\Cqrs\Query;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

/**
 * QueryHandlerInterface
 */
interface QueryHandlerInterface
{
    public function handle(QueryInterface $query);
}
