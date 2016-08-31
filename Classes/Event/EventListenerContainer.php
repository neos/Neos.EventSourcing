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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 * EventTransport
 */
class EventListenerContainer
{
    /**
     * @var array
     */
    protected $listener;

    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @param array $listener
     */
    public function __construct(array $listener)
    {
        $this->listener = $listener;
    }

    /**
     * @return string
     */
    public function getListenerClass(): string
    {
        return $this->listener[0];
    }

    /**
     * @return string
     */
    public function getListenerMethod(): string
    {
        return $this->listener[1];
    }

    /**
     * @param EventTransport $eventTransport
     */
    public function handle(EventTransport $eventTransport)
    {
        list($class, $method) = $this->listener;
        $handler = $this->objectManager->get($class);
        $handler->$method($eventTransport->getEvent(), $eventTransport->getMetaData());
    }
}
