<?php
namespace Ttree\Cqrs\Message\Resolver\Handler;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Message\Resolver\ResolverInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * AutoResolver
 *
 * @Flow\Scope("singleton")
 */
class AutoResolver implements ResolverInterface
{
    /**
     * @param  string $messageName
     * @return string
     */
    public function resolve($messageName): string
    {
        return $messageName . 'Handler';
    }
}
