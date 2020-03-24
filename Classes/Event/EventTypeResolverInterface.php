<?php
namespace Neos\EventSourcing\Event;

/**
 * Contract for an Event Type resolver that converts DomainEventInterface instances to the corresponding "Event Type" string representation, vice versa
 */
interface EventTypeResolverInterface
{
    /**
     * Return the event type for the given Event object
     *
     * @param DomainEventInterface $event An Domain Event instance
     * @return string The corresponding Event Type, for example "Some.Package:SomeEvent"
     */
    public function getEventType(DomainEventInterface $event): string;

    /**
     * Return the event classname for the given event type
     *
     * @param string $eventType The Event Type, for example "Some.Package:SomeEvent"
     * @return string The fully qualified class name of the corresponding Domain Event, for example "\Some\Package\Events\SomeEvent"
     */
    public function getEventClassNameByType(string $eventType): string;
}
