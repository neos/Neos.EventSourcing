<?php
namespace Ttree\Cqrs\Message;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Domain\Timestamp;
use TYPO3\Flow\Annotations as Flow;

/**
 * MessageMetadata
 */
class MessageMetadata
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
     * @var \DateTime
     */
    protected $timestamp;

    /**
     * @var array
     */
    protected $propertyBag = [];

    /**
     * @param string $aggregateName
     * @param string $aggregateIdentifier
     * @param \DateTime $timestamp
     */
    public function __construct(string $aggregateName, string $aggregateIdentifier, \DateTime $timestamp = null)
    {
        $this->aggregateName = $aggregateName;
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->timestamp = $timestamp ?: Timestamp::create();
    }

    /**
     * @return string
     */
    public function getAggregateName(): string
    {
        return $this->aggregateName;
    }

    /**
     * @return string
     */
    public function getAggregateIdentifier(): string
    {
        return $this->aggregateIdentifier;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function add(string $name, $value)
    {
        $this->propertyBag[$name] = $value;
    }

    /**
     * @param string $name
     */
    public function remove(string $name)
    {
        unset($this->propertyBag[$name]);
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function has(string $name): bool
    {
        return isset($this->propertyBag[$name]) && $this->propertyBag[$name] !== null;
    }
}
