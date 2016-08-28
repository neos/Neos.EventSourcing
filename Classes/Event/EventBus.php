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
     * @param EventInterface|FaultInterface $message
     * @return void
     */
    public function handle(EventInterface $message)
    {
        /** @var EventHandlerInterface[] $handlers */
        $handlers = $this->locator->getHandlers($message);

        foreach ($handlers as $handler) {
            try {
                $handler->handle($message);
            } catch (\Exception $exception) {
                if ($message instanceof FaultInterface) {
                    return;
                }
                $this->handle(new GenericFault($message, $handler, $exception));
            }
        }
    }
}
