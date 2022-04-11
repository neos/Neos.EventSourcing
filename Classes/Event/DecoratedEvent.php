<?php
declare(strict_types=1);
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

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;

/**
 * A decorator that wraps an DomainEventInterface adding metadata and/or an event identifier
 *
 * @Flow\Proxy(false)
 */
final class DecoratedEvent implements DomainEventInterface
{
    /**
     * @var DomainEventInterface
     */
    private $event;

    /**
     * @var array
     */
    private $metadata;

    /**
     * @var string|null
     */
    private $identifier;

    private function __construct(DomainEventInterface $event, array $metadata, ?string $identifier)
    {
        $this->event = $event;
        $this->metadata = $metadata;
        $this->identifier = $identifier;
    }

    public static function addMetadata(DomainEventInterface $event, array $metadata): DecoratedEvent
    {
        $identifier = null;
        if ($event instanceof DecoratedEvent) {
            $metadata = Arrays::arrayMergeRecursiveOverrule($event->metadata, $metadata);
            $identifier = $event->identifier;
            $event = $event->getWrappedEvent();
        }
        return new DecoratedEvent($event, $metadata, $identifier);
    }

    public static function addCausationIdentifier(DomainEventInterface $event, string $causationIdentifier): DecoratedEvent
    {
        DecoratedEvent::validateIdentifier($causationIdentifier);
        return DecoratedEvent::addMetadata($event, ['causationIdentifier' => $causationIdentifier]);
    }

    public static function addCorrelationIdentifier(DomainEventInterface $event, string $correlationIdentifier): DecoratedEvent
    {
        DecoratedEvent::validateIdentifier($correlationIdentifier);
        return DecoratedEvent::addMetadata($event, ['correlationIdentifier' => $correlationIdentifier]);
    }

    public static function addIdentifier(DomainEventInterface $event, string $identifier): DecoratedEvent
    {
        DecoratedEvent::validateIdentifier($identifier);
        $metadata = [];
        if ($event instanceof DecoratedEvent) {
            $metadata = $event->metadata;
            $event = $event->getWrappedEvent();
        }
        return new DecoratedEvent($event, $metadata, $identifier);
    }

    public function getWrappedEvent(): DomainEventInterface
    {
        return $this->event;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function hasIdentifier(): bool
    {
        return $this->identifier !== null;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    private static function validateIdentifier(string $identifier): void
    {
        if ($identifier === '') {
            throw new \InvalidArgumentException('Empty identifier provided', 1509109037);
        }
        if (\strlen($identifier) > 255) {
            throw new \InvalidArgumentException('Identifier must be 255 characters or less', 1509109039);
        }
    }
}
