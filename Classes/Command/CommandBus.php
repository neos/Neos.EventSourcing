<?php
namespace Neos\Cqrs\Command;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Event\EventType;
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
