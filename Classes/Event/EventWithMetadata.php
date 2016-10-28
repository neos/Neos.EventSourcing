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

/**
 * Wrapper container for a domain event that is enriched with metadata.
 * This class is immutable, if the event contained is implemented immutable.
 *
 * @Flow\Proxy(false)
 */
final class EventWithMetadata
{
    /**
     * @var EventInterface
     */
    protected $event;

    /**
     * @var EventMetadata
     */
    protected $metadata;

    /**
     * @param EventInterface $event
     * @param EventMetadata $metadata
     */
    public function __construct(EventInterface $event, EventMetadata $metadata)
    {
        $this->event = $event;
        $this->metadata = $metadata;
    }

    /**
     * @return EventInterface
     */
    public function getEvent(): EventInterface
    {
        return $this->event;
    }

    /**
     * @return EventMetadata
     */
    public function getMetadata(): EventMetadata
    {
        return $this->metadata;
    }
}
