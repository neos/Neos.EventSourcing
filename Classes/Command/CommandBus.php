<?php
namespace Ttree\Cqrs\Command;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Command\Exception\CommandBusException;
use Ttree\Cqrs\Command\Resolver\ResolverInterface;
use Ttree\Cqrs\Event\EventType;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 * CommandBus
 *
 * @Flow\Scope("singleton")
 */
class CommandBus implements CommandBusInterface
{
    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var ResolverInterface
     * @Flow\Inject
     */
    protected $resolver;

    /**
     * @var array
     */
    protected $queue = [];

    /**
     * @var boolean
     */
    protected $isHandling = false;

    /**
     * @param CommandInterface $command
     * @return void
     */
    public function handle(CommandInterface $command)
    {
        $this->queue[] = $command;

        if ($this->isHandling) {
            return;
        }

        $this->isHandling = true;

        try {
            while ($command = array_shift($this->queue)) {
                $this->getHandler($command)->handle($command);
            }
        } finally {
            $this->isHandling = false;
        }
    }

    /**
     * @param CommandInterface $message
     * @return CommandHandlerInterface
     * @throws CommandBusException
     * @todo Use CompileStatic to build a mapping between command and command handler during compilation
     */
    protected function getHandler(CommandInterface $message)
    {
        $messageName = EventType::get($message);

        $handlerClassName = $this->resolver->resolve($messageName);

        if (!$this->objectManager->isRegistered($handlerClassName)) {
            throw new CommandBusException(
                sprintf(
                    "Missing handler '%s' for command '%s'",
                    $handlerClassName,
                    $messageName
                )
            );
        }

        /** @var CommandHandlerInterface $handler */
        $handler = $this->objectManager->get($handlerClassName);

        if (!$handler instanceof CommandHandlerInterface) {
            throw new CommandBusException(
                sprintf(
                    "Handler '%s' returned by locator for command '%s' should implement CommandHandlerInterface",
                    $handlerClassName,
                    $messageName
                )
            );
        }

        return $handler;
    }
}
