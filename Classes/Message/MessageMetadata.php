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

/**
 * MessageMetadata
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
     * @param array $properties
     * @param \DateTimeImmutable $timestamp
     */
    public function __construct(array $properties = [], \DateTimeImmutable $timestamp = null)
    {
        $this->properties = $properties;
        $this->timestamp = $timestamp ?: Timestamp::create();
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return MessageMetadata
     */
    public function withProperty(string $name, $value)
    {
        return new static(array_merge($this->properties, [$name => $value]), $this->timestamp);
    }

    /**
     * @param array $properties
     * @return MessageMetadata
     */
    public function withProperties(array $properties)
    {
        return new static($properties, $this->timestamp);
    }

    /**
     * @param array $properties
     * @return MessageMetadata
     */
    public function andProperties(array $properties)
    {
        return new static(array_merge($this->properties, $properties), $this->timestamp);
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function contains(string $name): bool
    {
        return isset($this->properties[$name]) && $this->properties[$name] !== null;
    }
}
