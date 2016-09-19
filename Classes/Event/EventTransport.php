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

use Neos\Cqrs\Message\MessageMetadata;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventTransport
 *
 * @Flow\Proxy(false)
 */
class EventTransport
{
    /**
     * @var EventInterface
     */
    protected $event;

    /**
     * @var MessageMetadata
     */
    protected $metadata;

    /**
     * @param EventInterface $event
     * @param MessageMetadata $metadata
     */
    public function __construct(EventInterface $event, MessageMetadata $metadata)
    {
        $this->event = $event;
        $this->metadata = $metadata;
    }

    /**
     * @param EventInterface $event
     * @param MessageMetadata $metaData
     * @return EventTransport
     */
    public static function create(EventInterface $event, MessageMetadata $metaData): EventTransport
    {
        return new EventTransport($event, $metaData);
    }

    /**
     * @return EventInterface
     */
    public function getEvent(): EventInterface
    {
        return $this->event;
    }

    /**
     * @return MessageMetadata
     */
    public function getMetadata(): MessageMetadata
    {
        return $this->metadata;
    }

    /**
     * @param MessageMetadata $metadata
     * @return EventTransport
     */
    public function withMetadata(MessageMetadata $metadata)
    {
        return new static($this->event, $metadata);
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->metadata->getTimestamp();
    }
}
