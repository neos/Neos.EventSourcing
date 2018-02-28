<?php
namespace Neos\EventSourcing\EventListener\Exception;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\EventStore\RawEvent;
use Neos\EventSourcing\RuntimeException;

/**
 * Thrown if an exception occurs while handling an event in the corresponding listener
 */
class EventCantBeAppliedException extends RuntimeException
{
    /**
     * @var RawEvent
     */
    private $rawEvent;

    public function __construct($message = '', $code = 0, \Throwable $previous = null, RawEvent $rawEvent)
    {
        $this->rawEvent = $rawEvent;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return RawEvent
     */
    public function getRawEvent(): RawEvent
    {
        return $this->rawEvent;
    }

}