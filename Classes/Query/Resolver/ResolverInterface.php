<?php
namespace Ttree\Cqrs\Query\Resolver;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * LocatorInterface
 */
interface ResolverInterface
{
    /**
     * @param  string $messageName
     * @return string
     */
    public function resolve($messageName): string;
}
