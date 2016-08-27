# CRQS for Flow Framework

_This package is currently under development and not fully working, please don't use it in production._

This package is inspired by [LiteCQRS](https://github.com/beberlei/litecqrs-php) and [Broadway](https://github.com/qandidate-labs/broadway).

The goal of the project is to provide infrastructure to support CQRS/ES project based on Flow Framework

# Packages

The features are splitted in differents packages:

* **[Ttree.Cqrs](https://github.com/dfeyer/Ttree.Cqrs)**: this package, mostly infrastructure (interface, trait, abstract class) and the event/query bus
* **[Ttree.EventStore](https://github.com/dfeyer/Ttree.EventStore)**: event store to support event sourcing
* **[Ttree.EventStore.InMemoryStorageAdapter](https://github.com/dfeyer/Ttree.EventStore.InMemoryStorageAdapter)**: in memory event storage, mainly for testing
* **[Ttree.EventStore.DatabaseStorageAdapter](https://github.com/dfeyer/Ttree.EventStore.DatabaseStorageAdapter)**: doctrine dbal based event storage

More storage can be added later (Redis, ...).

# Components

Currently most components are included in the ```Ttree.Cqrs``` package. In the future some component can be splitted 
in distinct package for more flexibility. 

## Command


### CommandBus

* [x] **CommandBus**: default implementation, implement your own based on ```CommandBusInterface```

### Command

* [x] **Command**: implement your own based on ```CommandInterface```

Your command must simply implement the ```CommandInterface```. The ```CommandTrait``` add the 
method ```CommandInterface::getIdentifier```, the UUID of the command is lazy generated, so you 
can focus on your command logic.

    class ConfirmOrder implements CommandInterface
    {
        use CommandTrait;
    
        /**
         * @var string
         */
        protected $aggregateIdentifier;
    
        /**
         * @param string $aggregateIdentifier
         * @param float $duration
         */
        public function __construct(string $aggregateIdentifier)
        {
            $this->aggregateIdentifier = $aggregateIdentifier;
        }
    
        /**
         * @return string
         */
        public function getAggregateIdentifier(): string
        {
            return $this->aggregateIdentifier;
        }
    }

### CommandHandler

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
 
    use Ttree\Cqrs\Annotations as Cqrs;
    
    /**
     * @Cqrs\EventHandler(event="Your.Package.Event.ConfirmationFailed")
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

**Work in Progress:** Currently the annotation support only a full event name (class namespace with backslash 
replaced by dot), in a future version we will add support ```your.package.event.*.event``` (```*``` for any `
valid caracter except ```.``) or ```your.package.event.>``` (all events bellow the given namespace), 
this can offer great flexibility. The idea is based on NatsIO subject handling.

## EventStore

See package **Ttree.EventStore**.

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
