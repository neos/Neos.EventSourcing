<?php
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

use Neos\EventSourcing\Event\EventInterface;
use Neos\EventSourcing\Exception;

/**
 * Event wrapper which provides a causation id additional to the event
 */
final class EventWithCausationIdentifier implements EventWithMetadataInterface
{
    /**
     * @var EventInterface
     */
    private $event;

    /**
     * @var array
     */
    private $metadata;

    /**
     * EventWithCausationIdentifier constructor.
     *
     * @param EventInterface $event
     * @param string $causationIdentifier
     * @throws Exception
     */
    public function __construct(EventInterface $event, string $causationIdentifier)
    {
        $causationIdentifier = trim($causationIdentifier);
        if ($causationIdentifier === '') {
            throw new Exception('Empty causation identifier provided', 1509109337);
        }
        if (strlen($causationIdentifier) > 255) {
            throw new Exception('Causation identifier must be 255 characters or less', 1509109339);
        }
        if ($event instanceof EventWithMetadataInterface) {
            $this->event = $event->getEvent();
            $this->metadata = $event->getMetadata();
        } else {
            $this->event = $event;
            $this->metadata = [];
        }
        $this->metadata['causationIdentifier'] = $causationIdentifier;
    }

    /**
     * @return EventInterface
     */
    public function getEvent(): EventInterface
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
