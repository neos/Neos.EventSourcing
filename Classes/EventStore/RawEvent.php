<?php
namespace Neos\EventSourcing\EventStore;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * The raw event data including payload, metadata and technical meta information
 *
 * This class represents an event before it has been converted to an instance of EventInterface.
 * It is used by the EventStream which converts it on-the-fly and it will be passed to any when*() event listener
 * as optional argument.
 */
final class RawEvent
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var array
     */
    private $metadata;

    /**
     * @var int
     */
    private $version;

    /**
     * @var int
     */
    private $sequenceNumber;

    /**
     * @var \DateTimeInterface
     */
    protected $recordedAt;

    public function __construct(int $sequenceNumber, string $type, array $payload, array $metadata, int $version, string $identifier, \DateTimeInterface $recordedAt)
    {
        $this->sequenceNumber = $sequenceNumber;
        $this->type = $type;
        $this->payload = $payload;
        $this->metadata = $metadata;
        $this->version = $version;
        $this->identifier = $identifier;
        $this->recordedAt = $recordedAt;
    }

    public function getSequenceNumber(): int
    {
        return $this->sequenceNumber;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getRecordedAt(): \DateTimeInterface
    {
        return $this->recordedAt;
    }
}
