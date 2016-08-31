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

use Neos\Cqrs\Message\MessageInterface;
use Neos\Cqrs\Message\MessageMetadata;

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
