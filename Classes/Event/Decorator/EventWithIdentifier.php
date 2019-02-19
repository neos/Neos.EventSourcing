<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Event\Decorator;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Utility\Algorithms;

/**
 * Event wrapper which provides an identifier additional to the event
 */
final class EventWithIdentifier implements DomainEventWithIdentifierInterface
{
    /**
     * @var DomainEventInterface
     */
    private $event;

    /**
     * @var string
     */
    private $identifier;

    /**
     * EventWithMetadata constructor.
     *
     * @param DomainEventInterface $event
     * @param string $identifier
     */
    public function __construct(DomainEventInterface $event, string $identifier)
    {
        $this->event = $event instanceof DomainEventDecoratorInterface ? $event->getEvent() : $event;
        $this->identifier = $identifier;
    }

    public static function create(DomainEventInterface $event): self
    {
        return new static($event, Algorithms::generateUUID());
    }

    /**
     * @return DomainEventInterface
     */
    public function getEvent(): DomainEventInterface
    {
        return $this->event;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
