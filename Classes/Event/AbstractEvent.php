<?php
namespace Ttree\Cqrs\Event;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Domain\Timestamp;
use Ttree\Cqrs\Message\MessageMetadata;
use Ttree\Cqrs\Message\MessageTrait;
use TYPO3\Flow\Annotations as Flow;

/**
 * AbstractEvent
 */
abstract class AbstractEvent implements EventInterface
{
    use MessageTrait;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @param array $payload
     * @param MessageMetadata $metadata
     */
    public function __construct(array $payload, MessageMetadata $metadata = null)
    {
        $this->metadata = $metadata ?: new MessageMetadata(str_replace('\\', '.', get_called_class()), Timestamp::create());
        $this->payload = $payload;
    }

    /**
     * @param array $payload
     * @return EventInterface
     */
    public static function create(array $payload)
    {
        return new static($payload);
    }
}
