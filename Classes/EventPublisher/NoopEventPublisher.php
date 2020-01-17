<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventPublisher;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\DomainEvents;

/**
 * Some dummy Event Publisher that can be used if no events should be published at all
 */
final class NoopEventPublisher implements EventPublisherInterface
{
    public function publish(DomainEvents $events): void
    {
    }
}
