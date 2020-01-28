# Event Sourcing and CQRS for Flow Framework

_This package is currently under development and not fully tested, please don't use it in production._

Major Rewrite in process.. Stay tuned

### Minimal Setup

With 2.0 there is no default Event Store configured any more.
Instead the initial Event Store setup is now expected to be defined in the corresponding package or distribution.

The following configuration (to be put into `Settings.yaml`) configures an Event Store with the id "default" that
uses the current Flow database connection to persist events and is being used for all Event Listeners:

```yaml
Neos:
  EventSourcing:
    EventStore:
      stores:
        'default':
          storage: 'Neos\EventSourcing\EventStore\Storage\Doctrine\DoctrineEventStorage'
          listeners:
            '.*': true
```

This won't work with multiple Event Stores though, so it's recommended to use a unique namespace for the identifier and for the listener patterns.
The following configures an Event Store with the id "Your.Package:DefaultEventStore" that is being used for all Event Listeners of the `Your\Package` namespaces:

```yaml
Neos:
  EventSourcing:
    EventStore:
      stores:
        'Your.Package:DefaultEventStore':
          storage: 'Neos\EventSourcing\EventStore\Storage\Doctrine\DoctrineEventStorage'
          listeners:
            'Your\Package\.*': true
```

A slightly more advanced configuration could look like this:

```yaml
Neos:
  EventSourcing:
    EventStore:
      stores:
        'Your.Package:DefaultEventStore':
          storage: 'Neos\EventSourcing\EventStore\Storage\Doctrine\DoctrineEventStorage'
          storageOptions:
            eventTableName: 'your_package_events'
            backendOptions:
              dbname: 'other_db'

          # A class that implements the EventPublisherFactoryInterface
          eventPublisherFactory: 'Your\Package\SomeCustomEventPublisherFactory'

          listeners:
            'Your\Package\Domain\SomeListener':
              'some': 'publisher-option'
            'Your\Package\Domain\Some(Other|Third)Listener': true
```


### Basic Event Flow

* `Commands` are plain PHP objects, being the external Write API.
    * Commands SHOULD be immutable and final.
    * Commands SHOULD be written in *present tense* (Example: `CreateWorkspace`)
* For each command, a method on the corresponding `CommandHandler`
  is called. That's how you "dispatch" a command.
    * A Command Handler is a standard Flow singleton, without any required base class.
    * The handler methods SHOULD be called `handle[CommandName]([CommandName] $command)`
* Inside the `handle*` method of the command handler, one or multiple `Event`s are created from the command,
  possibly after checking soft constraints; or forwarding to an aggregate (not described here):
    * The event SHOULD be immutable and final.
    * The event MUST have the **annotation `@Flow\Proxy(false)`**
    * The event MUST implement the marker interface `Neos\EventSourcing\Event\DomainEventInterface`.
    * The event SHOULD be written in past tense. Example: `WorkspaceWasCreated`
* To actually store the event, the following must be done:
  * retrieve an instance of `Neos\EventSourcing\EventStore` (inject the default instance or use the `Neos\EventSourcing\EventStore\EventStoreFactory`)
  * specify the stream to which you want to publish events to: `$streamName = StreamName::fromString('some-stream');`
  * Commit the events by executing `$eventStore->commit($streamName, DomainEvents::withSingleEvent($event));`
* The EventStore stores the event into the *Storage* and publishes them in the *Event Bus*.
  * The Event Bus remembers that certain Event Listeners (== Projections) need to be updated.
* At the end of the request (on `shutdownObject()` of the EventBus), the job queue `neos-eventsourcing`
  receives an `CatchUpEventListenerJob` with the event listener which should be ran.
* Then, the event listeners are invoked asynchronously inside the queue.

### Aggregates

To Be Written

#### Aggregate Repository

This Framework does not provide an abstract Repository class for Aggregates, because an implementation is just a couple of lines of code and there is no useful abstraction that can be extracted. The Repository is just a slim wrapper around the EventStore and the Aggregate class. If you want to create a Repository for an Aggregate `Product` then the code would look like this:

```php
final class ProductRepository
{
    // inject an instance of EventStore somehow ...

    public function load(ProductIdentifier $id): Product
    {
        $streamName = ...; // Build the stream name from the identifier
        return Product::reconstituteFromEventStream($this->eventStore->load($streamName));
    }

    public function save(Product $product): void
    {
        $streamName = ...; // Build the stream name from $product->getIdentifier()
        $this->eventStore->commit($streamName, $product->pullUncommittedEvents(), $product->getReconstitutionVersion());
    }
}
```
