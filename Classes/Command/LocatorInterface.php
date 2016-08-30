<?php
namespace Ttree\Cqrs\Command;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

/**
 * LocatorInterface
 */
interface LocatorInterface
{
    /**
     * @param  string $messageName
     * @return \Closure
     */
    public function resolve($messageName): \Closure;
}
