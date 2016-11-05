<?php
namespace Neos\Cqrs\EventStore\Middleware;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\EventStore\EventStoreCommit;
use Neos\Cqrs\EventStore\WritableEvent;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;

/**
 * LoggingLayer
 */
class LoggingLayer implements EventStoreLayerInterface
{
    /**
     * @var SystemLoggerInterface
     * @Flow\Inject
     */
    protected $logger;

    /**
     * @param EventStoreCommit $commit
     * @param \Closure $next
     * @return EventStoreCommit
     */
    public function execute($commit, \Closure $next)
    {
        /** @var WritableEvent $event */
        foreach ($commit->getEvents() as $event) {
            $this->logger->log(vsprintf('message="Event pushed to the storage" eventType=%s streamName=%s', [
                $event->getType(),
                $commit->getStreamName()
            ]));
        }

        $response = $next($commit);

        /** @var WritableEvent $event */
        foreach ($commit->getEvents() as $event) {
            $this->logger->log(vsprintf('message="Event stored with success" eventType=%s streamName=%s', [
                $event->getType(),
                $commit->getStreamName()
            ]));
        }

        return $response;
    }
}
