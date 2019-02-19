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

/**
 * Event wrapper which provides a correlation id additional to the event
 */
final class EventWithCorrelationIdentifier implements DomainEventWithMetadataInterface
{
    /**
     * @var DomainEventInterface
     */
    private $event;

    /**
     * @var array
     */
    private $metadata;

    /**
     * EventWithCorrelationId constructor.
     *
     * @param DomainEventInterface $event
     * @param string $correlationIdentifier
     */
    public function __construct(DomainEventInterface $event, string $correlationIdentifier)
    {
        $correlationIdentifier = trim($correlationIdentifier);
        if ($correlationIdentifier === '') {
            throw new \InvalidArgumentException('Empty correlation identifier provided', 1509109037);
        }
        if (strlen($correlationIdentifier) > 255) {
            throw new \InvalidArgumentException('Correlation identifier must be 255 characters or less', 1509109039);
        }
        $this->event = $event instanceof DomainEventDecoratorInterface ? $event->getEvent() : $event;
        $this->metadata = $event instanceof DomainEventWithMetadataInterface ? $event->getMetadata() : [];
        $this->metadata['correlationIdentifier'] = $correlationIdentifier;
    }

    /**
     * @return DomainEventInterface
     */
    public function getEvent(): DomainEventInterface
    {
        return $this->event;
    }

    /**
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
