<?php
namespace Flowpack\Cqrs\Message\Resolver\Handler;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Message\Resolver\ResolverInterface;
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
        return sprintf('%sHandler', $messageName);
    }
}
