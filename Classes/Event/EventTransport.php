<?php
namespace Ttree\Cqrs\Event;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Message\MessageInterface;
use Ttree\Cqrs\Message\MessageMetadata;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventTransport
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
    protected $metaData;

    /**
     * @param EventInterface $event
     * @param MessageMetadata $metaData
     */
    public function __construct(EventInterface $event, MessageMetadata $metaData)
    {
        $this->event = $event;
        $this->metaData = $metaData;
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
     * @return EventInterface|MessageInterface
     */
    public function getEvent(): EventInterface
    {
        return $this->event;
    }

    /**
     * @return MessageMetadata
     */
    public function getMetaData(): MessageMetadata
    {
        return $this->metaData;
    }

    /**
     * @return string
     */
    public function getAggregateName(): string
    {
        return $this->metaData->getAggregateName();
    }

    /**
     * @return string
     */
    public function getAggregateIdentifier(): string
    {
        return $this->metaData->getAggregateIdentifier();
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp(): \DateTime
    {
        return $this->metaData->getTimestamp();
    }
}
