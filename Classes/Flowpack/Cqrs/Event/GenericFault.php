<?php
namespace Flowpack\Cqrs\Event;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Exception;
use Flowpack\Cqrs\Message\MessageInterface;
use Flowpack\Cqrs\Message\MessageTrait;
use TYPO3\Flow\Annotations as Flow;

/**
 * GenericFault
 */
class GenericFault implements FaultInterface
{
    use MessageTrait;

    const NAME = 'GenericFault';

    /** @var EventInterface */
    public $event;

    /** @var EventHandlerInterface */
    public $handler;

    /** @var Exception */
    public $exception;

    /**
     * @param MessageInterface $event
     * @param EventHandlerInterface $handler
     * @param \Exception $exception
     */
    public function __construct(MessageInterface $event, EventHandlerInterface $handler, \Exception $exception)
    {
        $this->event = $event;
        $this->handler = $handler;
        $this->exception = $exception;


        $this->setMetadata(
            self::NAME,
            new \DateTime()
        );

        $this->setPayload([
            'event' => $event,
            'handler' => $handler,
            'exception' => $exception
        ]);
    }
}
