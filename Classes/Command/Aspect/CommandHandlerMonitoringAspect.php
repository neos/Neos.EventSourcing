<?php
namespace Ttree\Cqrs\Command\Aspect;

/*
 * This file is part of the Medialib.Storage package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Log\SystemLoggerInterface;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class CommandHandlerMonitoringAspect
{
    /**
     * @var SystemLoggerInterface
     * @Flow\Inject
     */
    protected $logger;

    /**
     * @var boolean
     * @Flow\InjectConfiguration(path="command.monitoring.commandHandlerMonitoring.enabled")
     */
    protected $enabled;

    /**
     * @Flow\Around("within(Ttree\Cqrs\Command\CommandHandlerInterface) && method(public .*->handle())")
     * @param JoinPointInterface $joinPoint
     */
    public function log(JoinPointInterface $joinPoint)
    {
        if ($this->enabled) {
            $startTime = microtime(true);

            $joinPoint->getAdviceChain()->proceed($joinPoint);

            $command = $joinPoint->getMethodArgument('command');

            $this->logger->log(vsprintf('action=monitoring type=command-handler command="%s" elapsed_time=%f', [
                $command->getName(),
                microtime(true) - $startTime
            ]), LOG_DEBUG);
        } else {
            $joinPoint->getAdviceChain()->proceed($joinPoint);
        }
    }
}
