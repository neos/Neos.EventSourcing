<?php
namespace Flowpack\Cqrs\Event;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Domain\Timestamp;
use Flowpack\Cqrs\Exception;
use Flowpack\Cqrs\Message\MessageInterface;
use Flowpack\Cqrs\Message\MessageMetadata;
use Flowpack\Cqrs\Message\MessageTrait;
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
