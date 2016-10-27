<?php
namespace Neos\Cqrs\EventStore;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

final class StoredEvent
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
     * @var \DateTimeInterface
     */
    protected $recordedAt;

    public function __construct(string $identifier, string $type, array $payload, array $metadata, int $version, \DateTimeInterface $recordedAt)
    {
        $this->identifier = $identifier;
        $this->type = $type;
        $this->payload = $payload;
        $this->metadata = $metadata;
        $this->version = $version;
        $this->recordedAt = $recordedAt;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
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

    public function getRecordedAt(): \DateTimeInterface
    {
        return $this->recordedAt;
    }
}
