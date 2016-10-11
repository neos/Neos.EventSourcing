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

use Neos\Cqrs\Event\AggregateEventInterface;

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
     * @param AggregateEventInterface $event
     * @param array $metadata
     */
    public function recordThat(AggregateEventInterface $event, array $metadata = []);

    /**
     * @return array
     */
    public function pullUncommittedEvents(): array;
}
