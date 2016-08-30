<?php
namespace Ttree\Cqrs\Command;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

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
     * @var LocatorInterface
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
                $handler = $this->getHandler($command);
                $handler($command);
            }
        } finally {
            $this->isHandling = false;
        }
    }

    /**
     * @param CommandInterface $message
     * @return \Closure
     */
    protected function getHandler(CommandInterface $message)
    {
        $messageName = EventType::get($message);
        return $this->resolver->resolve($messageName);
    }
}
