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
 * Base implementation for an event-sourced aggregate root
 */
abstract class AbstractEventSourcedAggregateRoot extends AbstractAggregateRoot implements EventSourcedAggregateRootInterface
{
    /**
     * @var int
     */
    private $reconstitutionVersion = -1;

    /**
     * The version of the event stream at time of reconstitution
     * This is used to avoid race conditions
     *
     * @return int
     */
    final public function getReconstitutionVersion(): int
    {
        return $this->reconstitutionVersion;
    }

    /**
     * @param string $identifier
     * @param EventStream $stream
     * @return self
     */
    public static function reconstituteFromEventStream(string $identifier, EventStream $stream)
    {
        $instance = new static($identifier);
        $lastAppliedEventVersion = -1;
        foreach ($stream as $eventAndRawEvent) {
            $instance->apply($eventAndRawEvent->getEvent());
            $lastAppliedEventVersion = $eventAndRawEvent->getRawEvent()->getVersion();
        }
        $instance->reconstitutionVersion = $lastAppliedEventVersion;
        return $instance;
    }
}
