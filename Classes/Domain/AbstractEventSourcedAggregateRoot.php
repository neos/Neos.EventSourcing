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
use Neos\Cqrs\RuntimeException;

/**
 * AggregateRootTrait
 */
abstract class AbstractEventSourcedAggregateRoot extends AbstractAggregateRoot implements EventSourcedAggregateRootInterface
{
    /**
     * Note: This must not be private so it can be set during reconstitution via unserialize()
     *
     * @var int
     */
    protected $version = -1;

    /**
     * @return int
     */
    final public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param EventStream $stream
     * @throws RuntimeException
     */
    public function reconstituteFromEventStream(EventStream $stream)
    {
        if ($this->hasUncommittedEvents()) {
            throw new RuntimeException(sprintf('%s has already been reconstituted from the event stream.', get_class($this)), 1474547708762);
        }

        foreach ($stream as $eventTransport) {
            $this->apply($eventTransport->getEvent());
        }
    }
}
