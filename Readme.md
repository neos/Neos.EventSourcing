# Event Sourcing and CQRS for Flow Framework

Library providing interfaces and implementations for event-sourced applications. 

## Getting started

Install this package via composer:

```shell script
composer require neos/event-sourcing
```

### Setting up an Event Store

Since there could be multiple Event Stores simultaneously in one application, this package no longer comes with a pre-configured "default" store.
It is just a matter of a couple of lines of YAML to configure a custom store:

*Configuration/Settings.yaml:*
```yaml
Neos:
  EventSourcing:
    EventStore:
      stores:
        'Some.Package:EventStore':
          storage: 'Neos\EventSourcing\EventStore\Storage\Doctrine\DoctrineEventStorage'
```

This registers an Event Store, identified as "Some.Package:EventStore"<sup id="a1">[1](#f1)</sup>, that uses the provided Doctrine storage adapter that persists events in a
database table<sup id="a2">[2](#f2)</sup>.

To make use of the newly configured Event Store one more step is required in order to finish the setup (in this case to create the corresponding database table):

```shell script
./flow eventstore:setup Some.Package:EventStore
```

<details><summary>:information_source:&nbsp; <b>Note...</b></summary>

> By default, the Event Store persists events in the same database that is used for Flow persistence.
> But because that can be configured otherwise, this table is not generated via Doctrine migrations.
> If your application relies on the events table to exist, you can of course still add a Doctrine migration for it.
</details>

### Obtaining the Event Store

To get hold of an instance of a specific Event Store the `EventStoreFactory` can be used:

```php
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\Flow\Annotations as Flow;

class SomeClass {

    /**
     * @Flow\Inject
     * @var EventStoreFactory
     */
    protected $eventStoreFactory;

    function someMethod() {
        $eventStore = $this->eventStoreFactory->create('Some.Package:EventStore');
    }
}
```

<details><summary>:information_source:&nbsp; <b>Alternative ways...</b></summary>

Alternatively you can inject the Event Store directly:

```php
use Neos\EventSourcing\EventStore\EventStore;
use Neos\Flow\Annotations as Flow;

class SomeClass {

    /**
     * @Flow\Inject
     * @var EventStore
     */
    protected $eventStore;

    function someMethod() {
        // $this->eventStore->...
    }
}
```

But in this case you have to specify _which_ event store to be injected.
This can be done easily using Flow's [Object Framework](https://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartIII/ObjectManagement.html#object-framework):

*Configuration/Objects.yaml:*
```yaml
Some\Package\SomeClass:
  properties:
    'eventStore':
      object:
        factoryObjectName: Neos\EventSourcing\EventStore\EventStoreFactory
        arguments:
          1:
            value: 'Some.Package:EventStore'
```

If you use Flow 6.2 or newer, you can make use of the [virtual object configuration](https://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartIII/ObjectManagement.html#virtual-objects)
to simplify the process:

*Configuration/Objects.yaml:*
```yaml
'Some.Package:EventStore':
  className: Neos\EventSourcing\EventStore\EventStore
  factoryObjectName: Neos\EventSourcing\EventStore\EventStoreFactory
  arguments:
    1:
      value: 'Some.Package:EventStore'
```

```php
use Neos\EventSourcing\EventStore\EventStore;
use Neos\Flow\Annotations as Flow;

class SomeClass {

    /**
     * @Flow\Inject(name="Some.Package:EventStore")
     * @var EventStore
     */
    protected $eventStore;
}
```

And, finally, if you happen to use the event store from many classes, you could of course create a custom Event Store facade like:

*Classes/CustomEventStore.php*
```php
<?php
namespace Some\Package;

use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\EventSourcing\EventStore\EventStream;
use Neos\EventSourcing\EventStore\ExpectedVersion;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class CustomEventStore
{

    /**
     * @var EventStore
     */
    private $instance;

    public function __construct(EventStoreFactory $eventStoreFactory)
    {
        $this->instance = $eventStoreFactory->create('Some.Package:EventStore');
    }

    public function load(StreamName $streamName, int $minimumSequenceNumber = 0): EventStream
    {
        return $this->instance->load($streamName, $minimumSequenceNumber);
    }

    public function commit(StreamName $streamName, DomainEvents $events, int $expectedVersion = ExpectedVersion::ANY): void
    {
        $this->instance->commit($streamName, $events, $expectedVersion);
    }
}
```

and inject that.
</details>

### Writing events

<details><summary>Example event: <i>SomethingHasHappened.php</i></summary>

```php
<?php
namespace Some\Package;

use Neos\EventSourcing\Event\DomainEventInterface;

final class SomethingHasHappened implements DomainEventInterface
{
    /**
     * @var string
     */
    private $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

}
```
</details>

```php
<?php
$event = new SomethingHasHappened('some message');
$streamName = StreamName::fromString('some-stream');
$eventStore->commit($streamName, DomainEvents::withSingleEvent($event));
```

### Reading events

```php
<?php
$streamName = StreamName::fromString('some-stream');
$eventStream = $eventStore->load($streamName);
foreach ($eventStream as $eventEnvelope) {
    // the event as it's stored in the Event Store, including its global sequence number and the serialized payload
    $rawEvent = $eventEnvelope->getRawEvent();

    // the deserialized DomainEventInterface instance 
    $domainEvent = $eventEnvelope->getDomainEvent();
}
```

### Reacting to events

In order to react upon new events you'll need an [Event Listener](Glossary.md#event-listener):

```php
<?php
namespace Some\Package;

use Neos\EventSourcing\EventListener\EventListenerInterface;
use Some\Package\SomethingHasHappened;

class SomeEventListener implements EventListenerInterface
{

    public function whenSomethingHasHappened(SomethingHasHappened $event): void
    {
        // do something with the $event
    }

}
```

The `when*()` methods of classes implementing the `EventListenerInterface` will be invoked whenever a corresponding event is committed to the Event Store.

Since it is possible to have multiple Event Stores the listener has to be *registered* with the corresponding Store:

*Configuration/Settings.yaml:*
```yaml
Neos:
  EventSourcing:
    EventStore:
      stores:
        'Some.Package:EventStore':
          # ...
          listeners:
            'Some\Package\SomeEventListener': true
```

This registers the `Some\Package\SomeEventListener` so that it is updated whenever a corresponding event was committed to the "Some.Package:EventStore".

To register all/multiple listeners with an Event Store, you can use regular expressions, too:

*Configuration/Settings.yaml:*
```yaml
Neos:
  EventSourcing:
    EventStore:
      stores:
        'Some.Package:EventStore':
          # ...
          listeners:
            'Some\Package\.*': true
```
 
Keep in mind though that a listener can only ever by registered with a single Event Store (otherwise you'll get an exception at "compile time").

## Event Sourced Aggregate

The `neos/event-sourcing` package comes with a base class that can be used to implement [Event-Sourced Aggregates](Glossary.md#aggregate).

### Aggregate Construction

The `AbstractEventSourcedAggregateRoot` class has a private constructor. To create a fresh aggregate instance you should define a named constructor:

```php
<?php
declare(strict_types=1);
namespace Some\Package;

use Neos\EventSourcing\AbstractEventSourcedAggregateRoot;

final class SomeAggregate extends AbstractEventSourcedAggregateRoot
{
    /**
     * @var SomeAggregateId
     */
    private $id;

    public static function create(SomeAggregateId $id): self
    {
        $instance = new static();
        // This method will only be invoked once. Upon reconstitution only the when*() methods are called.
        // So we must never change the instance state directly (i.e. $instance->id = $id) but use events:
        $instance->recordThat(new SomeAggregateWasCreated($id));
        return $instance;
    }

    public function whenSomeAggregateWasCreated(SomeAggregateWasCreated $event): void
    {
        $this->id = $event->getId();
    }
}
```

### Aggregate Repository

This Framework does not provide an abstract Repository class for Aggregates, because an implementation is just a couple of lines of code and there is no useful abstraction that can be extracted.
The Repository is just a slim wrapper around the EventStore and the Aggregate class:

```php
final class ProductRepository
{
    /**
     * @var EventStore
     */
    private $eventStore;

    public function __construct(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    // this method is only required if the creation of the aggregate should be explicit (i.e. lead to a domain event being published)
    public function create(SomeAggregateId $id): SomeAggregate
    {
        $aggregate = SomeAggregate::create($id);
        $streamName = $this->getStreamName($id);
        $this->eventStore->commit($streamName, $aggregate->pullUncommittedEvents(), ExpectedVersion::NO_STREAM);
    }

    public function load(SomeAggregateId $id): SomeAggregate
    {
        $streamName = $this->getStreamName($id);
        return SomeAggregate::reconstituteFromEventStream($this->eventStore->load($streamName));
    }

    public function save(SomeAggregate $aggregate): void
    {
        $streamName = $this->getStreamName($aggregate->id());
        $this->eventStore->commit($streamName, $aggregate->pullUncommittedEvents(), $aggregate->getReconstitutionVersion());
    }

    private function getStreamName(SomeAggregateId $id): StreamName
    {
        // we assume that the aggregate stream name is "some-aggregate-<aggregate-id>"
        return StreamName::fromString('some-aggregate-' . $id);
    }

}
```

## Tutorial

See [Tutorial.md](Tutorial.md)

## Glossary

See [Glossary.md](Glossary.md)

---

<sup id="f1">1</sup>: The Event Store identifier is arbitrary, but it's good practice prefixing it with a package key in order to prevent naming clashes [↩](#a1)
<sup id="f2">2</sup>: The Doctrine Event storage uses the same database connection that is configured for Flow and persists events in a table `neos_eventsourcing_eventstore_events` by default – this can be adjusted, see [Settings.yaml](Configuration/Settings.yaml) [↩](#a2)
