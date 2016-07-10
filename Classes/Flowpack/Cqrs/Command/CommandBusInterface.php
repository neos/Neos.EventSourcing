<?php
namespace Flowpack\Cqrs\Command;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Command\CommandInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * CommandBusInterface
 */
interface CommandBusInterface
{
    /**
     * @param CommandInterface $command
     * @return void|MessageResultInterface
     */
    public function handle(CommandInterface $command);
}
