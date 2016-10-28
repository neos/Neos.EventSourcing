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

/**
 * The EventMetadata is a container for arbitrary metadata for Events.
 * This class is immutable.
 *
 * @api
 */
class EventMetadata
{
    const VERSION = 'neos.cqrs:version';
    const TIMESTAMP = 'neos.cqrs:timestamp';

    /**
     * @var array
     */
    private $properties = [];

    /**
     * @param array $properties An associative array of properties that this EventMetadata contains.
     */
    public function __construct(array $properties)
    {
        $this->properties = $properties;
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
     * Return a new instance of the EventMetadata with the property set to the value.
     * Any existing property with that name will be overwritten.
     *
     * @param string $name The property name to set.
     * @param mixed $value The value to set the property to.
     * @return EventMetadata
     * @api
     */
    public function withProperty(string $name, $value): EventMetadata
    {
        return new static(array_merge($this->properties, [$name => $value]));
    }

    /**
     * Return a new instance of this EventMetadata with the given properties.
     * All existing properties will be fully replaced.
     *
     * @param array $properties An associative array of properties to set.
     * @return EventMetadata
     * @api
     */
    public function withProperties(array $properties): EventMetadata
    {
        return new static($properties);
    }

    /**
     * Return a new instance of this EventMetadata with the given properties merged.
     * Any existing properties will be overwritten, other properties will stay untouched.
     *
     * @param array $properties An associative array of properties to merge.
     * @return EventMetadata
     * @api
     */
    public function andProperties(array $properties): EventMetadata
    {
        return new static(array_merge($this->properties, $properties));
    }

    /**
     * Check if this EventMetadata contains the given property.
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
