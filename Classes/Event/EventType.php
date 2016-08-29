<?php
namespace Ttree\Cqrs\Event;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * EventType
 * @todo maybe move this to a service, nightmare to test
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
