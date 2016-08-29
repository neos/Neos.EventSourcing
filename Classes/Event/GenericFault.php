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
     * @var EventListenerInterface
     */
    protected $listener;

    /**
     * @var \Exception
     */
    protected $exception;

    /**
     * @param EventInterface|MessageInterface $event
     * @param EventListenerInterface $listener
     * @param \Exception $exception
     */
    public function __construct(MessageInterface $event, EventListenerInterface $listener, \Exception $exception)
    {
        $this->event = $event;
        $this->listener = $listener;
        $this->exception = $exception;
    }
}
