# Glossary

A list of Event Sourcing specific terms with a definition on how we currently understand it.
*Note:* This list is not authoritative but merely our take and in parts greatly simplified to the implementation in this package. Please get in touch with us or create an issue if you think something is missing, incorrect or unclear!

### Table of contents

* [Aggregate](#aggregate)
* [Bounded Context](#bounded-context)
* [Causation Identifier](#causation-identifier)
* [Concurrency](#concurrency)
* [Correlation Identifier](#correlation-identifier)
* [CQRS](#cqrs)
* [Domain Event](#domain-event)
* [Event Correlation](#event-correlation)
* [Event Identifier](#event-identifier)
* [Event Listener](#event-listener)
* [Event Store](#event-store)
* [Event Store Streams](#event-store-streams)
* [Eventual Consistency](#eventual-consistency)
* [Hard Constraint](#hard-constraint)
* [Immediate Consistency](#immediate-consistency)
* [Process Manager](#process-manager)
* [Projection](#projection)
* [Query Model](#query-model)
* [Read Model](#read-model)
* [Soft Constraint](#soft-constraint)
* [Strong Consistency](#strong-consistency)
* [View Model](#view-model)
* [Write Model](#write-model)

## Aggregate

The term "aggregate" is a bit misleading because it has multiple meanings in different contexts.
In his book "Domain-Driven Design"<sup id="a1">[1](#f1)</sup> Eric Evans describes Aggregates as:

> [...] a cluster of associated objects that we treat as a unit for the purpose of data changes.

An *Event-Sourced* Aggregate usually refers to a [Write Model](#write-model) that is used to achieve [Strong Consistency](#strong-consistency).

*Note:* Due to its name, this term occurs high up in this glossary. That shouldn't reflect the importance of this concept – we actually think that a lot of applications work without Aggregates, embracing [Eventual Consistency](#eventual-consistency)

<details><summary><b>Example</b></summary>

Unique sequential numbering is a common requirement.
This is a simple example of an `InvoiceNumbering` aggregate that `InvoiceNumberWasAssigned` events always contain a unique invoice number:

```php
<?php
declare(strict_types=1);
namespace Some\Package;

use Neos\EventSourcing\AbstractEventSourcedAggregateRoot;

final class InvoiceNumbering extends AbstractEventSourcedAggregateRoot
{

    /**
     * @var int
     */
    private $highestAssignedInvoiceNumber = 0;

    public static function create(): static
    {
        return new static();
    }

    public function assignInvoiceNumber(string $invoiceId): void
    {
        $this->recordThat(new InvoiceNumberWasAssigned($invoiceId, $this->highestAssignedInvoiceNumber + 1));
    }

    public function whenInvoiceNumberWasAssigned(InvoiceNumberWasAssigned $event): void
    {
        $this->highestAssignedInvoiceNumber = max($this->highestAssignedInvoiceNumber, $event->getInvoiceNumber());
    }
}
```

To load an Aggregate we can use it's `reconstituteFromEventStream()` method:

```php
<?php

// assign unique invoice number

$streamName = StreamName::fromString('invoice-numbering');
$eventStream = $this->eventStore->load($streamName);
$aggregate = InvoiceNumbering::reconstituteFromEventStream($eventStream);
$aggregate->assignInvoiceNumber($invoiceId);
```

The aggregate events are only persisted once the events were committed to the event store.
To extract the new events from the aggregate, the `pullUncommittedEvents()` is used and to make sure that no events are published to the aggregate's event stream
in the meantime, we specify the "reconstitution version" of the aggregate as *expected version*: 

```php
$eventStore->commit($streamName, $aggregate->pullUncommittedEvents(), $aggregate->getReconstitutionVersion());
```
</details>

## Bounded Context

A logical boundary between (sub) domains.

The implementation of such boundary can have all kinds of characteristics.
In the simplest form it is just a mental separation between logical parts of the application.
On the other end of the spectrum the boundaries are represented by completely separate development teams and software stacks.

In the context of this package, a separate Bounded Context should _at least_ use a separate Event Store (see [Readme](Readme.md#multiple-event-stores) on how to set up multiple instances)

*Note:* The term "Bounded Context" originates from Domain-Driven Design and is not directly related to Event-Sourcing, but since it's so important, we decided to include it in this list.

## Causation Identifier

An optional identifier that can be assigned to events to communicate what _caused_ the event. This could be the identifier of a Command or a preceding event.
A Causation Identifier can be useful for debugging for example in order to trace back the origin of a given event.
See [Event Correlation](#event-correlation)

<details><summary><b>Example</b></summary>

```php
<?php
public function whenOrderWasFinalized(OrderWasFinalized $event, RawEvent $rawEvent): void
{
    // send order confirmation ...

    // set the id of the handled event as causation identifier of the new event
    $newEvent = DecoratedEvent::addCausationIdentifier(
        new OrderConfirmationWasSent($payload),
        $rawEvent->getIdentifier()
    ];
    $this->eventStore->commit($streamName, DomainEvents::withSingleEvent($newEvent));
}
```
</details>

## Concurrency

The fact of two or more computations are happening at the same time.

Often "same time" can actually mean seconds or even minutes apart:
For example if a user updates data via some Web form, and the underlying resource has been changed from another user in the meantime.
In classic applications the default behavior in such cases is usually "last in wins" such that previous changes might be overridden by the second update.

## Correlation Identifier

An optional identifier that can be assigned to events to _correlate_ them with 
See [Event Correlation](#event-correlation)

<details><summary><b>Example</b></summary>

```php
<?php
public function whenOrderWasFinalized(OrderWasFinalized $event): void
{
    // send order confirmation ...

    // correlate the new event with the order identifier of the handled event
    $newEvent = DecoratedEvent::addCorrelationIdentifier(
        new OrderConfirmationWasSent($payload),
        $event->getOrderId()
    ];
    $this->eventStore->commit($streamName, DomainEvents::withSingleEvent($newEvent));
}
```
</details>

## CQRS

stands for "Command Query Responsibility Segregation" and represents the pattern to use a different model to _update_ information than the model you use to
_read_ information.
See [Write Model](#write-model) & [Read Model](#read-model)

## Domain Event

According to Martin Fowler a _Domain Event_ "captures the memory of something interesting which affects the domain"<sup id="a2">[2](#f2)</sup>.

Whether something is "interesting" might be a matter of discussions of course. But it should be clear that this is about incidents that happened in the domain logic rather than technical things (like mouse events in Javascript) – unless they are part
of the domain of course.

Because an event is something that happened in the past, they should be formulated as verbs in the _Past Tense_ form.<sup id="a3">[3](#f3)</sup>.

In this package a Domain Event is represented as a PHP class that...

* ...**MUST** implement the marker interface `Neos\EventSourcing\Event\DomainEventInterface`
* ...**SHOULD** be immutable and `final`
* ...**SHOULD** be formulated in _Present Perfect Tense_ (for example `MessageWasSent`)
* ...**MUST** have a getter for every field that is specified as constructor argument (or use public fields) so that it can be (de)serialized automatically<sup id="a4">[4](#f4)</sup>

<details><summary><b>Example</b></summary>

```php
<?php
declare(strict_types=1);
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

## Event Correlation

Especially in larger applications it can get very difficult to keep track of the event flow. Commands can lead to multiple events that in turn can lead to other follow-up events, potentially all in different Event Store Streams.
It is a good practice to correlate those events so that the flow can be traced back to the originating trigger.
With this practice every Event is enriched with _three_ identifiers before it is dispatched:

* The [Event Identifier](#event-identifier) is the globally unique id of the event (assigned automatically if not specified explicitly)
* The [Causation Identifier](#causation-identifier) is the id of the preceding message that let to the new event
* The [Correlation Identifier](#correlation-identifier) an id all messages of one process share. It is usually generated once and then copied for all succeeding events that were triggered by the original message

<details><summary><b>Example</b></summary>

```php
<?php
class SomeCommandHandler
{
    public function handleFinalizeOrder(FinalizeOrder $command): void
    {
        // validate command ...

        // create a new DomainEventInterface instance
        $event = new OrderWasFinalized($command->getOrderId());
        // ...with the command id as the *causation identifier*
        $event = DecoratedEvent::addCausationIdentifier($event, $command->getId());
        // ...and a new *correlation identifier* (alternatively the correlation id could be generated at command time)
        $correlationId = Algorithms::generateUUID();
        $event = DecoratedEvent::addCorrelationIdentifier($event, $correlationId);

        // ...

        // publish event
        $this->eventStore->commit($streamName, DomainEvents::withSingleEvent($event));
    }
}
```

```php
<?php
class SomeProcessManager implements EventListenerInterface
{
    public function whenOrderWasFinalized(OrderWasFinalized $event, RawEvent $rawEvent): void
    {
        // send order confirmation ...
    
        // create a new DomainEventInterface instance
        $newEvent = new OrderConfirmationWasSent($event->getOrderId(), $recipientId);
        // ...with the original event's identifier as *causation identifier*
        $newEvent = DecoratedEvent::addCausationIdentifier($newEvent, $rawEvent->getIdentifier());
        // ...and the same *correlation identifier*
        $newEvent = DecoratedEvent::addCorrelationIdentifier($newEvent, $rawEvent->getMetadata()['correlationIdentifier']);

        // ...

        // publish event
        $this->eventStore->commit($streamName, DomainEvents::withSingleEvent($newEvent));
    }
}
```
</details>

## Event Identifier

A globally unique identifier (usually a UUID) that is assigned to an event when it is committed to the Event Store unless it has been assigned manually before.

<details><summary><b>Example</b></summary>

```php
<?php
// assign event id manually
$eventWithId = DecoratedEvent::addIdentifier($domainEvent, 'some-id');
```
</details>

See [Event Correlation](#event-correlation)

## Event Listener

A piece of code that is invoked whenever a corresponding [Domain Event](#domain-event) is dispatched.
In this package Event Listeners are all classes that implement the `Neos\EventSourcing\EventListener\EventListenerInterface` interface.
Every listener needs at least one `when*()` method that...
* ...is public
* ...is not static
* ...expects an instance of the corresponding Domain Event as first argument
* ...optionally expects an instance of `RawEvent` as second argument
* ...is called `when<EventClassName>`
* ...has no return type

<details><summary><b>Example</b></summary>

```php
<?php
namespace Some\Package;

use Neos\EventSourcing\EventListener\EventListenerInterface;
use Neos\EventSourcing\EventStore\RawEvent;
use Some\Package\SomethingHasHappened;
use Some\Package\SomethingElseHasHappened;

class SomeEventListener implements EventListenerInterface
{

    public function whenSomethingHasHappened(SomethingHasHappened $event): void
    {
        // do something with the $event
    }

    public function whenSomethingElseHasHappened(SomethingElseHasHappened $event, RawEvent $rawEvent): void
    {
        // do something with the $event and/or $rawEvent
    }

}
```
</details>


Special types of Event Listeners are [Projectors](#projection) and [Process Managers](#process-manager).

## Event Store

The Event Store provides an API to load and persist Domain Events.
Unlike other databases it only ever *appends* events to [streams](#event-store-streams), they are never *updated* or *deleted*.

This package comes with a `default` Event Store pre-configured that (by default) uses the `DoctrineEventStorage` to store events in a database table.
Additional Event Store instances can be configured, see [Readme](Readme.md#multiple-event-stores).

## Event Store Streams

An Event Store can contain multiple streams.

When _writing_ (i.e. committing) events to the Event Store, the target stream has to be specified. Events are always appended to that stream and are assigned a `version` that
behaves like an autogenerated sequence in a regular database _within the given stream_.
Furthermore a `sequenceNumber` is assigned to the event, that acts like a global autogenerated index and is _unique_ throughout the whole Event Store.

Events can be _read_ from a specific stream or from – what we call it – _virtual_ streams.<sup id="a5">[5](#f5)</sup>.
The following virtual streams are currently supported:

* `StreamName::all()` will load events from _all_ streams of the Event Store
* `StreamName::forCategory('some-category')` will load events from all streams starting with the given string (e.g. "some-category-foo", "some-category-bar", ...)
* `StreamName::forCorrelationId('some-correlation-id')` will load events with the given [Correlation Identifier](#correlation-identifier)

The events are always sorted by their global `sequenceNumber` so that the ordering is deterministic.

## Eventual Consistency

*tbd*

## Hard Constraint

*tbd*

## Immediate Consistency

See [Strong Consistency](#strong-consistency)

## Process Manager

*tbd*

## Projection

*tbd*

## Query Model

See [Read Model](#read-model)

## Read Model

aka "Query Model" or "View Model".

A model that is optimized for reading performance.

In the context of this package this is typically implemented with a database table that is updated via [Projections](#projection).
But it could also be an Elastic Search index or some files in the file system to name a few.

In order to keep Write performance fast and reliable, the Read Model is usually updated [asynchronously](#eventual-consistency).

## Soft Constraint

An invariant that can fail if the corresponding model is [not consistent yet](#eventual-consistency).
Usually it is a check that is performed against the [Read Model](#read-model) 
See also [Hard Constraints](#hard-constraint) 

## Strong Consistency

aka "Immediate Consistency".

<details><summary><b>Example</b></summary>

The following SQL statement updates the user row with ID 1 only if it still has the expected version (15 in this case):

```sql
UPDATE users SET name = "New Name", version = version + 1 WHERE id = 1 AND version = 15
```

If a similar query had modified the user in the meantime this query would not update the row.

</details>


## View Model

See [Read Model](#read-model)

## Write Model

A model that is optimized for writing performance used to enforce [Hard Constraints](#hard-constraint).


---

<sup id="f1">1</sup>: Domain-Driven Design: Tackling Complexity in the Heart of Software, Eric Evans, Addison-Wesley Professional (30. August 2003) [↩](#a1)

<sup id="f2">2</sup>: Martin Fowler on _Domain Events_: https://martinfowler.com/eaaDev/DomainEvent.html [↩](#a2)

<sup id="f3">3</sup>: We even use _Present Perfect Tense_ for Domain Events because that reads better in conjunction with `when*()` handlers (`whenMessageSent` => `whenMessageWasSent`) [↩](#a3)

<sup id="f4">4</sup>: The [Symfony Serializer](https://symfony.com/doc/current/components/serializer.html) is used for event serialization and normalization, so instances have to be compatible with the `ObjectNormalizer` [↩](#a4)

<sup id="f5">5</sup>: The "virtual streams" are heavily inspired by [eventstore.org](https://eventstore.org/) [↩](#a5)
