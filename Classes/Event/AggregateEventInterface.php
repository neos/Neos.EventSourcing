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
 * Interface for events which are related to aggregates
 */
interface AggregateEventInterface extends EventInterface
{
    /**
     * Returns the identifier of the aggregate the event is related to
     *
     * @return string
     */
    public function getIdentifier(): string;

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
    public function setIdentifier(string $identifier);
}
