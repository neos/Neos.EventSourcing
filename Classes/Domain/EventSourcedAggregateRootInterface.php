<?php
namespace Neos\Cqrs\Domain;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\EventStore\EventStream;

/**
 * AggregateRootInterface
 */
interface EventSourcedAggregateRootInterface extends AggregateRootInterface
{
    /**
     * @param EventStream $stream
     * @return void
     */
    public function reconstituteFromEventStream(EventStream $stream);
}
