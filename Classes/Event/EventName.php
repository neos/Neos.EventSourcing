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
 * EventName
 */
class EventName
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param $object
     * @throws RuntimeException
     */
    public function __construct($object)
    {
        if (!is_object($object)) {
            throw new RuntimeException('The given value is not of type object', 1472333934);
        }
        $this->name = get_class($object);
    }

    /**
     * @param object $object
     * @return EventName
     */
    public static function create($object): EventName
    {
        return new EventName($object);
    }

    /**
     * @return string
     */
    function __toString()
    {
        return $this->name;
    }
}
