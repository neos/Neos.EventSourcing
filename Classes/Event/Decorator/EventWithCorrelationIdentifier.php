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
 * Event wrapper which provides a correlation id additional to the event
 */
final class EventWithCorrelationIdentifier implements EventWithMetadataInterface
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
     * EventWithCorrelationId constructor.
     *
     * @param EventInterface $event
     * @param string $correlationIdentifier
     * @throws Exception
     */
    public function __construct(EventInterface $event, string $correlationIdentifier)
    {
        $correlationIdentifier = trim($correlationIdentifier);
        if ($correlationIdentifier === '') {
            throw new Exception('Empty correlation identifier provided', 1509109037);
        }
        if (strlen($correlationIdentifier) > 255) {
            throw new Exception('Correlation identifier must be 255 characters or less', 1509109039);
        }
        if ($event instanceof EventWithMetadataInterface) {
            $this->event = $event->getEvent();
            $this->metadata = $event->getMetadata();
        } else {
            $this->event = $event;
            $this->metadata = [];
        }
        $this->metadata['correlationIdentifier'] = $correlationIdentifier;
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
