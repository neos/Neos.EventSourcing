<?php
namespace Ttree\Cqrs\Event;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\EventListener\EventListenerInterface;
use Ttree\Cqrs\EventListener\EventListenerLocatorInterface;
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
