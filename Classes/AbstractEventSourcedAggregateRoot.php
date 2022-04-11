<?php
declare(strict_types=1);
namespace Neos\EventSourcing;

use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventStream;

abstract class AbstractEventSourcedAggregateRoot implements EventRecordingInterface
{

    /**
     * @var int
     */
    private $reconstitutionVersion = -1;

    /**
     * @var DomainEvents|null
     */
    private $recordedEvents;

    final public function __construct()
    {
    }

    final public function recordThat(DomainEventInterface $event): void
    {
        $this->apply($event);
        if ($this->recordedEvents === null) {
            $this->recordedEvents = DomainEvents::withSingleEvent($event);
        } else {
            $this->recordedEvents = $this->recordedEvents->appendEvent($event);
        }
    }

    final public function pullUncommittedEvents(): DomainEvents
    {
        if ($this->recordedEvents === null) {
            return DomainEvents::createEmpty();
        }
        $events = $this->recordedEvents;
        $this->recordedEvents = null;
        return $events;
    }

    final public function getReconstitutionVersion(): int
    {
        return $this->reconstitutionVersion;
    }

    final public static function reconstituteFromEventStream(EventStream $stream): static
    {
        $instance = new static();
        $lastAppliedEventVersion = -1;
        foreach ($stream as $eventEnvelope) {
            $instance->apply($eventEnvelope->getDomainEvent());
            $lastAppliedEventVersion = $eventEnvelope->getRawEvent()->getVersion();
        }
        $instance->reconstitutionVersion = $lastAppliedEventVersion;
        return $instance;
    }

    final public function apply(DomainEventInterface $event): void
    {
        if ($event instanceof DecoratedEvent) {
            $event = $event->getWrappedEvent();
        }
        try {
            $methodName = sprintf('when%s', (new \ReflectionClass($event))->getShortName());
        } catch (\ReflectionException $exception) {
            throw new \RuntimeException(sprintf('Could not determine event handler method name for event %s in class %s', \get_class($event), \get_class($this)), 1540745153, $exception);
        }
        if (method_exists($this, $methodName)) {
            $this->$methodName($event);
        }
    }
}
