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
     * @return string HandlerId
     */
    public function resolve($messageName)
    {
        return str_replace("\\Command\\", "\\CommandHandler\\", $messageName) . 'Handler';
    }
}
