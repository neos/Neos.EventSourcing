<?php
namespace Neos\EventSourcing\Event;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\EventSourcing\Exception;

/**
 * Base class for events which are related to aggregates
 */
abstract class AbstractAggregateEvent implements AggregateEventInterface
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * Returns the identifier of the aggregate the event is related to
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Sets the aggregate identifier for this event.
     *
     * This method can only be called once per event instance. It is usually invoked by the recordThat() method in
     * a concrete aggregate object.
     *
     * @param string $identifier
     * @return void
     * @throws Exception
     */
    public function setIdentifier(string $identifier)
    {
        if ($this->identifier !== null) {
            throw new Exception(sprintf('The aggregate identifier of this %s has already been set (%s) and can only be set once.', get_class($this), $this->identifier), 1474969482419);
        }
        $this->identifier = $identifier;
    }
}
