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
     * @param EventTransport $transport
     * @return void
     */
    public function handle(EventTransport $transport)
    {
        /** @var EventListenerInterface[] $handlers */
        $handlers = $this->locator->getListeners($transport->getEvent());

        /** @var \Closure $handler */
        foreach ($handlers as $handler) {
            try {
                $handler($transport);
            } catch (\Exception $exception) {
                if ($transport instanceof FaultInterface) {
                    return;
                }
                $this->handle(new GenericFault($transport, $handler, $exception));
            }
        }
    }
}
