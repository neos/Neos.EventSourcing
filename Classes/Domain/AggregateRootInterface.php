<?php
namespace Neos\EventSourcing\Domain;

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

/**
 * AggregateRootInterface
 */
interface AggregateRootInterface
{
    /**
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * @param EventInterface $event
     */
    public function recordThat(EventInterface $event);

    /**
     * @return EventInterface[]
     */
    public function pullUncommittedEvents(): array;
}
