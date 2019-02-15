# Event Sourcing and CQRS for Flow Framework

_This package is currently under development and not fully tested, please don't use it in production._

Major Rewrite in process.. Stay tuned


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
  * retrieve an instance of `Neos\EventSourcing\EventStore\EventStoreManager`
  * retrieve the event store by stream name: `$eventStore = $this->eventStoreManager->getEventStoreForStreamName(StreamName::fromString('Your.Package:StreamNameHere'));`
  * Commit the events by executing `$eventStore->commit($streamName, DomainEvents::withSingleEvent($event));`
* The EventStore stores the event into the *Storage* and publishes them in the *Event Bus*.
  * The Event Bus remembers that certain Event Listeners (== Projections) need to be updated.
* At the end of the request (on `shutdownObject()` of the EventBus), the job queue `neos-eventsourcing`
  receives an `CatchUpEventListenerJob` with the event listener which should be ran.
* Then, the event listeners are invoked asynchronously inside the queue.
    

### Aggregates

To Be Written
