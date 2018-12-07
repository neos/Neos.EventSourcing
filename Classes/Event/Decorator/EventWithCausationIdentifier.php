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
 * Event wrapper which provides a causation id additional to the event
 */
final class EventWithCausationIdentifier implements DomainEventWithMetadataInterface
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
     * EventWithCausationIdentifier constructor.
     *
     * @param DomainEventInterface $event
     * @param string $causationIdentifier
     */
    public function __construct(DomainEventInterface $event, string $causationIdentifier)
    {
        $causationIdentifier = trim($causationIdentifier);
        if ($causationIdentifier === '') {
            throw new \InvalidArgumentException('Empty causation identifier provided', 1509109337);
        }
        if (strlen($causationIdentifier) > 255) {
            throw new \InvalidArgumentException('Causation identifier must be 255 characters or less', 1509109339);
        }
        if ($event instanceof DomainEventWithMetadataInterface) {
            $this->event = $event->getEvent();
            $this->metadata = $event->getMetadata();
        } else {
            $this->event = $event;
            $this->metadata = [];
        }
        $this->metadata['causationIdentifier'] = $causationIdentifier;
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
