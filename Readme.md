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

Your command must simply implement the ```CommandInterface```.

    class ConfirmOrder implements CommandInterface
    {
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

### EventBus

* [x] **EventBus**: default implementation, implement your own based on ```EventBusInterface```

### EventHandlerLocator

* [x] **EventHandlerLocator**: based on class annotations, implement your own based on ```EventHandlerLocatorInterface```

### Event

* [x] **Event**: implement your own based on ```EventInterface```

This interface contains no methods, so you are free to focus on your domain. The interface is used by Flow to provide 
infrastructure helpers (monitoring, debugging, ...).

    class ProductedOrdered implements EventInterface
    {
        /**
         * @var string
         */
        protected $productIdentifier;
    
        /**
         * @var integer
         */
        protected $amount;
    
        /**
         * @param string $publicIdentifier
         */
        public function __construct(string $productIdentifier, int $amount)
        {
            $this->productIdentifier = $productIdentifier;
            $this->amount = $amount;
        }
    
        /**
         * @return string
         */
        public function getProductIdentifier(): string
        {
            return $this->productIdentifier;
        }
    
        /**
         * @return string
         */
        public function getAmount(): int
        {
            return $this->amount;
        }
    }

### Generic Fault (WIP)

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
