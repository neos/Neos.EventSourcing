<?php
namespace Flowpack\Cqrs\Command;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * CommandHandlerInterface
 */
interface CommandHandlerInterface
{
    /**
     * @param CommandInterface $command
     */
    public function handle(CommandInterface $command);
}
