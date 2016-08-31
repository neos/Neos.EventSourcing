<?php
namespace Neos\Cqrs\Domain;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Event\EventInterface;

/**
 * AggregateRootInterface
 */
interface AggregateRootInterface
{
    /**
     * @return string
     */
    public function getAggregateIdentifier(): string;

    /**
     * @param EventInterface $event
     * @return void
     */
    public function recordThat(EventInterface $event);

    /**
     * @return array
     */
    public function pullUncommittedEvents(): array;
}
