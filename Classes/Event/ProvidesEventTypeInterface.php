<?php
namespace Neos\Cqrs\Event;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Interface for event classes which explicitly provide the identifier for the event type they are representing.
 */
interface ProvidesEventTypeInterface
{
    /**
     * Returns the identifier of the event type the event class represents.
     *
     * Example: "Acme.MyApplication:SomethingImportantHasHappened"
     *
     * @return string
     */
    public static function getEventType(): string;
}
