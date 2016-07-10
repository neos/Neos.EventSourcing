<?php
namespace Flowpack\Cqrs\Query;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * QueryHandlerInterface
 */
interface QueryHandlerInterface
{
    public function handle(QueryInterface $query);
}
