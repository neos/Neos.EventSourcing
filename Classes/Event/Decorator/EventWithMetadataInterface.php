<?php
namespace Neos\EventSourcing\Event\Decorator;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\EventInterface;

interface EventWithMetadataInterface extends EventInterface
{
    /**
     * @return EventInterface
     */
    public function getEvent(): EventInterface;

    /**
     * @return array
     */
    public function getMetadata(): array;
}