<?php
namespace Ttree\Cqrs\Event;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Message\MessageInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * GenericFault
 */
class GenericFault implements FaultInterface
{
    /**
     * @var MessageInterface
     */
    protected $event;

    /**
     * @var EventHandlerInterface
     */
    protected $handler;

    /**
     * @var \Exception
     */
    protected $exception;

    /**
     * @param EventInterface|MessageInterface $event
     * @param EventHandlerInterface $handler
     * @param \Exception $exception
     */
    public function __construct(MessageInterface $event, EventHandlerInterface $handler, \Exception $exception)
    {
        $this->event = $event;
        $this->handler = $handler;
        $this->exception = $exception;
    }
}
