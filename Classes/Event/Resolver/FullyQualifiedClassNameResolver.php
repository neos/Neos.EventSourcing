<?php


namespace Neos\EventSourcing\Event\Resolver;


use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\Event\EventTypeResolverInterface;

class FullyQualifiedClassNameResolver implements EventTypeResolverInterface
{

    public function getEventType(DomainEventInterface $event): string
    {
        return get_class($event);
    }

    public function getEventClassNameByType(string $eventType): string
    {
        return $eventType;
    }
}