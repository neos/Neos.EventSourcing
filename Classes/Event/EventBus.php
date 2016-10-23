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

use Neos\Cqrs\Event\Exception\EventBusException;
use Neos\Cqrs\EventListener\EventListenerLocator;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Utility\TypeHandling;

/**
 * @Flow\Scope("singleton")
 */
class EventBus
{
    /**
     * @var EventListenerLocator
     * @Flow\Inject
     */
    protected $locator;

    /**
     * @var SystemLoggerInterface
     * @Flow\Inject
     */
    protected $logger;

    /**
     * @param EventWithMetadata $transport
     * @throws \Exception
     */
    public function handle(EventWithMetadata $transport)
    {
        $listeners = $this->locator->getListeners($transport->getEvent());
        /** @var \callable $listener */
        foreach ($listeners as $listener) {
            try {
                call_user_func($listener, $transport->getEvent(), $transport->getMetadata());
            } catch (\Exception $exception) {
                $this->logger->logException($exception);
                throw new EventBusException(sprintf('An exception occurred while handling event "%s": %s (%s)', TypeHandling::getTypeForValue($transport->getEvent()), $exception->getMessage(), $exception->getCode()), 1472675781, $exception);
            }
        }
    }
}
