<?php
namespace Ttree\Cqrs\Event;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * EventBus
 *
 * @Flow\Scope("singleton")
 */
class EventBus implements EventBusInterface
{
    /**
     * @var EventHandlerLocatorInterface
     * @Flow\Inject
     */
    protected $locator;

    /**
     * @param EventTransport $transport
     * @return void
     */
    public function handle(EventTransport $transport)
    {
        /** @var EventHandlerInterface[] $handlers */
        $handlers = $this->locator->getHandlers($transport->getEvent());

        foreach ($handlers as $handler) {
            try {
                $handler->handle($transport);
            } catch (\Exception $exception) {
                if ($transport instanceof FaultInterface) {
                    return;
                }
                $this->handle(new GenericFault($transport, $handler, $exception));
            }
        }
    }
}
