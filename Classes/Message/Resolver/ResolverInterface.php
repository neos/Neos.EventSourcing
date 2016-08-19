<?php
namespace Ttree\Cqrs\Message\Resolver;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * ResolverInterface
 */
interface ResolverInterface
{
    /**
     * @param  string $messageName
     * @return string HandlerId
     */
    public function resolve($messageName);
}
