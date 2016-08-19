<?php
namespace Ttree\Cqrs\Event;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Domain\Timestamp;
use Ttree\Cqrs\Exception;
use Ttree\Cqrs\Message\MessageInterface;
use Ttree\Cqrs\Message\MessageMetadata;
use Ttree\Cqrs\Message\MessageTrait;
use TYPO3\Flow\Annotations as Flow;

/**
 * GenericFault
 */
class GenericFault implements FaultInterface
{
    use MessageTrait;

    /**
     * @param MessageInterface $event
     * @param EventHandlerInterface $handler
     * @param \Exception $exception
     */
    public function __construct(MessageInterface $event, EventHandlerInterface $handler, \Exception $exception)
    {
        $this->metadata = new MessageMetadata(get_called_class(), Timestamp::create());

        $this->setPayload([
            'event' => $event,
            'handler' => $handler,
            'exception' => $exception
        ]);
    }

    /**
     * @return EventInterface
     */
    public function getEvent(): EventInterface
    {
        return $this->getPayload()['event'];
    }

    /**
     * @return EventHandlerInterface
     */
    public function getHandler(): EventHandlerInterface
    {
        return $this->getPayload()['handler'];
    }

    /**
     * @return Exception
     */
    public function getException(): Exception
    {
        return $this->getPayload()['exception'];
    }
}
