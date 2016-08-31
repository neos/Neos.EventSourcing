<?php
namespace Neos\Cqrs\Event;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\EventListener\EventListenerInterface;
use Neos\Cqrs\EventListener\EventListenerLocatorInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;

/**
 * EventBus
 *
 * @Flow\Scope("singleton")
 */
class EventBus implements EventBusInterface
{
    /**
     * @var EventListenerLocatorInterface
     * @Flow\Inject
     */
    protected $locator;

    /**
     * @var SystemLoggerInterface
     * @Flow\Inject
     */
    protected $logger;

    /**
     * @param EventTransport $transport
     * @throws \Exception
     */
    public function handle(EventTransport $transport)
    {
        /** @var EventListenerInterface[] $handlers */
        $handlers = $this->locator->getListeners($transport->getEvent());

        foreach ($handlers as $handler) {
            try {
                $handler->handle($transport);
            } catch (\Exception $exception) {
                $this->logger->logException($exception);
                throw $exception;
            }
        }
    }
}
