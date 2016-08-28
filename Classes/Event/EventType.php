<?php
namespace Ttree\Cqrs\Event;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Domain\Timestamp;
use Ttree\Cqrs\Exception;
use Ttree\Cqrs\Message\MessageInterface;
use Ttree\Cqrs\Message\MessageMetadata;
use Ttree\Cqrs\Message\MessageTrait;
use Ttree\Cqrs\RuntimeException;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventType
 */
class EventType
{
    /**
     * @param object $object
     * @return string
     */
    public static function get($object): string
    {
        return get_class($object);
    }
}
