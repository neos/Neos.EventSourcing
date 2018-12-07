<?php
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
use Neos\EventSourcing\Event\EventTypeResolver;
use Neos\Flow\Annotations as Flow;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @Flow\Scope("singleton")
 *
 * Converts instances of EventInterface to serializable arrays vice versa
 */
final class EventNormalizer
{

    /**
     * @Flow\Inject
     * @var EventTypeResolver
     */
    protected $eventTypeResolver;

    /**
     * @var Serializer
     */
    private $serializer;

    protected function initializeObject(): void
    {
        // TODO: make normalizers configurable
        $normalizers = [new DateTimeNormalizer(), new JsonSerializableNormalizer(), new ObjectNormalizer()];
        $this->serializer = new Serializer($normalizers);
    }

    public function normalize(DomainEventInterface $event): array
    {
        return $this->serializer->normalize($event);
    }

    public function denormalize(array $eventData, string $eventType): DomainEventInterface
    {
        // TODO allow to hook into event type => class conversion in order to enable upcasting, ...
        $eventClassName = $this->eventTypeResolver->getEventClassNameByType($eventType);
        return $this->serializer->denormalize($eventData, $eventClassName);
    }
}
