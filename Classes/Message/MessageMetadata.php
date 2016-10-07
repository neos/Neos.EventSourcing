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
use TYPO3\Flow\Annotations as Flow;

/**
 * The MessageMetadata is a container for arbitrary metadata for Commands and Events.
 * This class is immutable.
 *
 * @api
 */
class MessageMetadata
{
    /**
     * @var \DateTimeImmutable
     */
    protected $timestamp;

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @param array $properties An associative array of properties that this MessageMetadata contains.
     * @param \DateTimeImmutable $timestamp Optional. The timestamp when the Message was created. Defaults to the current timestamp.
     */
    public function __construct(array $properties = [], \DateTimeImmutable $timestamp = null)
    {
        $this->properties = $properties;
        $this->timestamp = $timestamp ?: Timestamp::create();
    }

    /**
     * The timestamp when the message was created.
     *
     * @return \DateTimeImmutable
     * @api
     */
    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    /**
     * Returns the associative properties array containing the metadata.
     *
     * @return array
     * @api
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Returns the value of the specified property
     *
     * @param string $propertyName Name of the property
     * @return mixed
     * @api
     */
    public function getProperty(string $propertyName)
    {
        return (isset($this->properties[$propertyName]) ? $this->properties[$propertyName] : null);
    }

    /**
     * Return a new instance of the MessageMetadata with the property set to the value.
     * Any existing property with that name will be overwritten.
     *
     * @param string $name The property name to set.
     * @param mixed $value The value to set the property to.
     * @return MessageMetadata
     * @api
     */
    public function withProperty(string $name, $value): MessageMetadata
    {
        return new static(array_merge($this->properties, [$name => $value]), $this->timestamp);
    }

    /**
     * Return a new instance of this MessageMetadata with the given properties.
     * All existing properties will be fully replaced.
     *
     * @param array $properties An associative array of properties to set.
     * @return MessageMetadata
     * @api
     */
    public function withProperties(array $properties): MessageMetadata
    {
        return new static($properties, $this->timestamp);
    }

    /**
     * Return a new instance of this MessageMetadata with the given properties merged.
     * Any existing properties will be overwritten, other properties will stay untouched.
     *
     * @param array $properties An associative array of properties to merge.
     * @return MessageMetadata
     * @api
     */
    public function andProperties(array $properties): MessageMetadata
    {
        return new static(array_merge($this->properties, $properties), $this->timestamp);
    }

    /**
     * Check if this MessageMetadata contains the given property.
     *
     * @param string $name The property name to check for existence.
     * @return boolean True if the property exists and is not null, false otherwise.
     * @api
     */
    public function contains(string $name): bool
    {
        return isset($this->properties[$name]) && $this->properties[$name] !== null;
    }
}
