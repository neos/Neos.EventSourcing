<?php
namespace Flowpack\Cqrs\Event;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Message\MessageInterface;
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
     * @param MessageInterface $message
     * @return void
     */
    public function handle(MessageInterface $message)
    {
        /** @var EventHandlerInterface[] $handlers */
        $handlers = $this->locator->getHandlers($message);

        foreach ($handlers as $handler) {
            try {
                $handler->handle($message);
            } catch (\Exception $e) {

                if ($message instanceof FaultInterface) {
                    return;
                }

                $this->handle(new GenericFault(
                    $message,
                    $handler,
                    $e
                ));
            }
        }
    }
}
