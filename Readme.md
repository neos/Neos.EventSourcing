# CRQS for Flow Framework

_This package is currently under development and not fully working, please don't use it in production._

This package is inspired by [LiteCQRS](https://github.com/beberlei/litecqrs-php) and [Broadway](https://github.com/qandidate-labs/broadway).

The goal of the project is to provide infrastructure to support CQRS/ES project based on Flow Framework

# Packages

The features are splitted in differents packages:

* **Flowpack.Cqrs**: this package, mostly infrastructure (interface, trait, abstract class) and the event/query bus
* **Flowpack.EventStore**: event store to support event sourcing
* **Flowpack.EventStore.InMemoryStorageAdapter**: in memory event storage, mainly for testing
* **Flowpack.EventStore.DatabaseStorageAdapter**: doctrine dbal based event storage

More storage can be added later (Redis, ...).

# Components

Currently most components are included in the ```Flowpack.Cqrs``` package. In the future some component can be splitted 
in distinct package for more flexibility. 

## Command

* [x] **CommandBus**: default implementation, implement your own based on ```CommandBusInterface```
* [x] **Command**: implement your own based on ```CommandInterface```
* [x] **CommandHandler**: implement your own based on ```CommandHandlerInterface```

### Monitoring

You can disable (enabled by default) the command handler performance monitoring. The monitoring is implemented with AOP, 
check ```Settings.yaml``` for the configuration.

## Domain

* [x] **AggregateRoot**: implement your own based on ```AggregateRootInterface``` with ```AggregateRootTrait```
* [x] **Repository**: implement your own based on ```RepositoryInterface```

## Event

* [x] **EventBus**: default implementation, implement your own based on ```EventBusInterface```
* [x] **EventHandlerLocator**: based on class annotations, implement your own based on ```EventHandlerLocatorInterface```
* [x] **Event**: implement your own based on ```EventInterface``` + ```AbstractEvent```
* [x] **GenericFault**: event triggered by the EventBus is an event handler throw an exception

### How to register your event handler ?

This package promote small classes, an EventHandler is a class that handle a single type of message:
 
    use Flowpack\Cqrs\Annotations as Cqrs;
    
    /**
     * @Cqrs\EventHandler(event="Your\Package\Event\ConfirmationFailed")
     */
    class NotifySupportOnConfirmationFailed implements EventHandlerInterface
    {
        /**
         * @param EventInterface $event
         */
        public function handle(EventInterface $event)
        {
            ...
        }
    }

**Work in Progress:** For the final version of the package we should change how the event are named. Currently we use the 
 full class namespace. We used a lots NatsIO as a queue server, and we love how NatsIO handle subject and especialy how
 you can listen to subject with magic caracter like ```*``` and ```>```. So the final naming of the event could be the 
 full class namespace, lowercased with backslashes replaced by dots and uppercamel case to be prefixed with dots. 
 So in our example ```Your\Package\Event\ConfirmationFailed``` will become ```your.package.event.confirmation.failed```
 
 With this new naming convention the annotation will be improved to support ```your.package.event.*.failed``` (all 
 failed events) or ```your.package.event.>``` (all events in the your package), this can offer great flexibility and
 consistency with NatsIO conventions.

## EventStore

See package **Flowpack.EventStore**.

## Message

Messaging infrastructure, base class/traits to build your own Events.

## Query

[not documented, work in progress]

Acknowledgments
---------------

Development sponsored by [ttree ltd - neos solution provider](http://ttree.ch).

We try our best to craft this package with a lots of love, we are open to sponsoring, support request, ... just contact us.

License
-------

Licensed under MIT, see [LICENSE](LICENSE)
