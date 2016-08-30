<?php
namespace Ttree\Cqrs\Query\Resolver;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

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
