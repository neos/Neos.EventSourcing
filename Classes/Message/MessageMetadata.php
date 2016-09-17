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
     * @throws MessageMetadataException
     */
    public function add(string $name, $value)
    {
        if ($this->contains($value)) {
            throw new MessageMetadataException(sprintf('The given value "%s" exist'), 1472853526);
        }
        $this->properties[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @return MessageMetadata
     */
    public function remove(string $name)
    {
        unset($this->properties[$name]);
        return $this;
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
