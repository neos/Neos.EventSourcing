<?php
namespace Neos\Cqrs\Event\Middleware;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Event\EventTypeResolver;
use Neos\Cqrs\Event\EventWithMetadata;
use Neos\Cqrs\EventStore\EventStoreCommit;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;

/**
 * LoggingLayer
 */
class LoggingLayer implements EventBusLayerInterface
{
    /**
     * @var SystemLoggerInterface
     * @Flow\Inject
     */
    protected $logger;

    /**
     * @var EventTypeResolver
     * @Flow\Inject
     */
    protected $eventTypeResolver;

    /**
     * @param EventWithMetadata $event
     * @param \Closure $next
     * @return EventStoreCommit
     */
    public function execute($event, \Closure $next)
    {
        $response = $next($event);

        $this->logger->log(vsprintf('service="EventBus" message="Event published with success" eventType=%s', [
            $this->eventTypeResolver->getEventType($event->getEvent()),
        ]));

        return $response;
    }
}
