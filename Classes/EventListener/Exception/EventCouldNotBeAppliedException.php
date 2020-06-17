<?php
declare(strict_types=1);
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

use Neos\EventSourcing\EventListener\EventListenerInterface;
use Neos\EventSourcing\EventStore\EventEnvelope;
use Throwable;

/**
 * Exception that is thrown if an event could not be applied to the corresponding listener
 */
class EventCouldNotBeAppliedException extends \Exception
{
    /**
     * @var EventEnvelope
     */
    private $eventEnvelope;

    /**
     * @var EventListenerInterface
     */
    private $eventListener;

    public function __construct(string $message, int $code, Throwable $previous, EventEnvelope $eventEnvelope, EventListenerInterface $eventListener)
    {
        parent::__construct($message, $code, $previous);
        $this->eventEnvelope = $eventEnvelope;
        $this->eventListener = $eventListener;
    }

    public function getEventEnvelope(): EventEnvelope
    {
        return $this->eventEnvelope;
    }

    public function getEventListener(): EventListenerInterface
    {
        return $this->eventListener;
    }
}
