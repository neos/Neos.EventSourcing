<?php
namespace Ttree\Cqrs\Command;

/*
 * This file is part of the Medialib.Storage package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Domain\Timestamp;
use Ttree\Cqrs\Message\MessageMetadata;
use Ttree\Cqrs\Message\MessageTrait;
use TYPO3\Flow\Annotations as Flow;

/**
 * AbstractCommand
 */
abstract class AbstractCommand implements CommandInterface
{
    use MessageTrait;

    /**
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->metadata = new MessageMetadata(get_called_class(), Timestamp::create());
        $this->payload = $payload;
    }
}
