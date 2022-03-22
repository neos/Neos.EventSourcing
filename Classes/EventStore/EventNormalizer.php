<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventStore;

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
use Neos\EventSourcing\Event\EventTypeResolverInterface;
use Neos\EventSourcing\EventStore\Normalizer\ProxyAwareObjectNormalizer;
use Neos\EventSourcing\EventStore\Normalizer\ValueObjectNormalizer;
use Neos\Flow\Annotations as Flow;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @Flow\Scope("singleton")
 *
 * Converts instances of EventInterface to serializable arrays vice versa
 */
final class EventNormalizer
{

    /**
     * @var EventTypeResolverInterface
     */
    private $eventTypeResolver;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @param EventTypeResolverInterface $eventTypeResolver
     */
    public function __construct(EventTypeResolverInterface $eventTypeResolver)
    {
        $this->eventTypeResolver = $eventTypeResolver;

        // TODO: make normalizers configurable
        $normalizers = [
            new BackedEnumNormalizer(),
            new DateTimeNormalizer(),
            new JsonSerializableNormalizer(),
            new ValueObjectNormalizer(),
            new ProxyAwareObjectNormalizer()
        ];
        $this->serializer = new Serializer($normalizers);
    }

    /**
     * @param DomainEventInterface $event
     * @return array
     * @throws SerializerException
     */
    public function normalize(DomainEventInterface $event): array
    {
        return $this->serializer->normalize($event);
    }

    /**
     * @param array $eventData
     * @param string $eventType
     * @return DomainEventInterface
     * @throws SerializerException
     */
    public function denormalize(array $eventData, string $eventType): DomainEventInterface
    {
        // TODO allow to hook into event type => class conversion in order to enable upcasting, ...
        $eventClassName = $this->eventTypeResolver->getEventClassNameByType($eventType);
        /** @var DomainEventInterface $event */
        $event = $this->serializer->denormalize($eventData, $eventClassName);
        return $event;
    }

    /**
     * Return the event type for the given Event object
     *
     * @param DomainEventInterface $event An Domain Event instance
     * @return string The corresponding Event Type, for example "Some.Package:SomeEvent"
     */
    public function getEventType(DomainEventInterface $event): string
    {
        return $this->eventTypeResolver->getEventType($event);
    }
}
