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
use Neos\Utility\Arrays;

/**
 * Event wrapper which provides metadata additional to the event
 */
final class EventWithMetadata implements DomainEventWithMetadataInterface
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
     * EventWithMetadata constructor.
     *
     * @param DomainEventInterface $event
     * @param array $metadata
     */
    public function __construct(DomainEventInterface $event, array $metadata)
    {
        $this->event = $event;
        $metadataOfInnerEvent = EventDecoratorUtilities::extractMetadata($event);
        $this->metadata = Arrays::arrayMergeRecursiveOverrule($metadataOfInnerEvent, $metadata);
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
