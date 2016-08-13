<?php
namespace Flowpack\Cqrs\Message;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Event\EventInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * MessageResultInterface
 */
trait MessageTrait
{
    /**
     * @var string
     */
    protected $aggregateName;

    /**
     * @var string
     */
    protected $aggregateIdentifier;

    /**
     * @var MessageMetadata
     */
    protected $metadata;

    /**
     * @var array
     */
    protected $payload;

    /**
     * @return array
     */
    final public function getMetadata()
    {
        return [
            'name' => $this->metadata->getName(),
            'timestamp' => $this->metadata->getTimestamp()
        ];
    }

    /**
     * Should be called on message creating time (in message constructor)
     *
     * @param array $payload
     * @return static
     */
    final public function setPayload(array $payload): EventInterface
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * @return array
     */
    final public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @return string
     */
    final public function getAggregateName(): string
    {
        return $this->aggregateName;
    }

    /**
     * @param string $aggregateName
     * @return static
     */
    final public function setAggregateName(string $aggregateName): EventInterface
    {
        $this->aggregateName = $aggregateName;
        return $this;
    }

    /**
     * @param string $aggregateIdentifier
     * @return static
     */
    final public function setAggregateIdentifier(string $aggregateIdentifier): EventInterface
    {
        $this->aggregateIdentifier = $aggregateIdentifier;
        return $this;
    }

    /**
     * @return string
     */
    final public function getAggregateIdentifier()
    {
        return $this->aggregateIdentifier;
    }

    /**
     * @return string
     */
    final public function getName()
    {
        return $this->metadata->getName();
    }

    /**
     * @return \DateTime
     */
    final public function getTimestamp()
    {
        return $this->metadata->getTimestamp();
    }

    /**
     * @return array
     */
    final public function toArray()
    {
        return [
            'metadata' => $this->getMetadata(),
            'payload' => $this->getPayload()
        ];
    }
}
