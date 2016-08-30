<?php
namespace Ttree\Cqrs\Command;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

/**
 * CommandBusInterface
 */
interface CommandBusInterface
{
    /**
     * @param CommandInterface $command
     * @return void
     */
    public function handle(CommandInterface $command);
}
