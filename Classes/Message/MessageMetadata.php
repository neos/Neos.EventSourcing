<?php
namespace Neos\Cqrs\Message;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Domain\Timestamp;
use Neos\Cqrs\Message\Resolver\Exception\MessageMetadataException;

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
     * @var \DateTimeImmutable
     */
    protected $timestamp;

    /**
     * @var array
     */
    protected $propertyBag = [];

    /**
     * @param string $aggregateIdentifier
     * @param string $aggregateName
     * @param \DateTimeImmutable $timestamp
     */
    public function __construct(string $aggregateIdentifier, string $aggregateName, \DateTimeImmutable $timestamp = null)
    {
        $this->aggregateIdentifier = $aggregateIdentifier;
        $this->aggregateName = $aggregateName;
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
     * @return \DateTimeImmutable
     */
    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return MessageMetadata
     * @throws MessageMetadataException
     */
    public function add(string $name, $value)
    {
        if ($this->has($value)) {
            throw new MessageMetadataException(sprintf('The given value "%s" exist and the metadata object is immutable'), 1472853526);
        }
        $this->propertyBag[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @return MessageMetadata
     */
    public function remove(string $name)
    {
        unset($this->propertyBag[$name]);
        return $this;
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
