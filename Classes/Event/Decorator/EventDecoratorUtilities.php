<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Event\Decorator;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Utility\Algorithms;

/**
 * Helper functions to work with possibly decorated events (e.g. extracting metadata, the ID, or the raw event)
 */
final class EventDecoratorUtilities
{

    /**
     * Extract the undecorated event from a possibly decorated event. Is a no-op when called on a non-decorated
     * event.
     *
     * @param DomainEventInterface $event the possibly decorated event
     * @return DomainEventInterface the undecorated event
     */
    public static function extractUndecoratedEvent(DomainEventInterface $event): DomainEventInterface
    {
        if ($event instanceof DomainEventDecoratorInterface) {
            // we are in a decoration chain, but did not find our target interface (yet);
            // so we further traverse the chain.
            return self::extractUndecoratedEvent($event->getEvent());
        }

        return $event;
    }

    /**
     * Extract the Identifier from the (possibly decorated) event.
     *
     * It walks the decoration chain until it finds the outermost DomainEventWithIdentifierInterface, in which
     * case it will return that one's identifier.
     *
     * Otherwise, if we traversed to the innermost event, we'll generate a new identifier.
     *
     * @param DomainEventInterface $event
     * @return string the identifier for the $event; either taken from DomainEventWithIdentifierInterface or generated anew.
     */
    public static function extractIdentifier(DomainEventInterface $event): string
    {
        if ($event instanceof DomainEventWithIdentifierInterface) {
            // first case: we found our target interface in the decoration chain
            return $event->getIdentifier();
        }

        if ($event instanceof DomainEventDecoratorInterface) {
            // we are in a decoration chain, but did not find our target interface (yet);
            // so we further traverse the chain.
            return self::extractIdentifier($event->getEvent());
        }

        // Default, in case we traversed the chain until the end and did not find anything.
        return Algorithms::generateUUID();
    }

    /**
     * Extract the Metadata from the (possibly decorated) event.
     *
     * It walks the decoration chain until it finds the outermost DomainEventWithMetadataInterface, in which
     * case it will return that one's metadata.
     *
     * Otherwise, if we traversed to the innermost event, we'll generate a new identifier.
     *
     * When using EventWithMetadata, it will return all merged metadata, because EventWithMetadata uses this
     * method itself internally.
     *
     * @param DomainEventInterface $event
     * @return array the metadata for the $event, or empty array if none found.
     */
    public static function extractMetadata(DomainEventInterface $event): array
    {
        if ($event instanceof DomainEventWithMetadataInterface) {
            // first case: we found our target interface in the decoration chain
            return $event->getMetadata();
        }
        if ($event instanceof DomainEventDecoratorInterface) {
            // we are in a decoration chain, but did not find our target interface (yet);
            // so we further traverse the chain.
            return self::extractMetadata($event->getEvent());
        }

        // Default, in case we traversed the chain until the end and did not find anything.
        return [];
    }
}
