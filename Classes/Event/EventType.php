<?php
namespace Ttree\Cqrs\Event;

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
