# CRQS for Flow Framework

_This package is currently under development and not fully working, please don't use it in production._

This package is inspired by [LiteCQRS](https://github.com/beberlei/litecqrs-php) and [Broadway](https://github.com/qandidate-labs/broadway).

The goal of the project is to provide infrastructure to support CQRS/ES project based on Flow Framework

# Requirements

* PHP 7
* Flow 3.3 LTS

# Packages

The features are splitted in differents packages:

* **[Ttree.Cqrs](https://github.com/dfeyer/Ttree.Cqrs)**: mostly infrastructure (interface, trait, abstract class) and the event/query bus
* **[Ttree.Cqrs.MonitoringHelper](https://github.com/dfeyer/Ttree.Cqrs.MonitoringHelper)**: aspect to monitor performance of the ```Ttree.Cqrs``` infrastructure
* **[Ttree.EventStore](https://github.com/dfeyer/Ttree.EventStore)**: event store to support event sourcing
* **[Ttree.EventStore.InMemoryStorageAdapter](https://github.com/dfeyer/Ttree.EventStore.InMemoryStorageAdapter)**: in memory event storage, mainly for testing
* **[Ttree.EventStore.DatabaseStorageAdapter](https://github.com/dfeyer/Ttree.EventStore.DatabaseStorageAdapter)**: doctrine dbal based event storage

More storage can be added later (Redis, ...).

# Folder Structure

This is a PSR4 package structure:
 
    Your.Package/
      Application/
        Controller/
        Command/
        Service/
      
      CommandHandler/
      EventListener/
      
      Domain/
        Service/
        Aggregate/
          YourAggregate/
            Command/
            Event/
            Service/
            YourAggregate.php
            YourAggregateRepository.php
          
      Query/
        Projection/
            [WhatYouNeed]Projection.php
            
        Model/
            YourModel.php
            YourModelFinder.php

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

Your command must simply implement the ```CommandHandlerInterface```.

    class ButtonCommandHandler implements CommandHandlerInterface
    {
        /**
         * @var ButtonRepository
         * @Flow\Inject
         */
        protected $buttonRepository;
    
        /**
         * @param CreateButton $command
         */
        public function handleCreateButton(CreateButton $command)
        {
            $button = new Button($command->getAggregateIdentifier(), $command->getPublicIdentifier());
            $button->changeLabel($command->getLabel());
    
            $this->buttonRepository->save($button);
        }
    
    }
    
The command handler locator can throw exception is something wrong with your command handler definition, please check your 
system log to have more informations.

### Monitoring

You can disable (enabled by default) the command handler performance monitoring. The monitoring is implemented with AOP, 
check ```Settings.yaml``` for the configuration.

## Domain

* [x] **AggregateRoot**: implement your own based on ```AggregateRootInterface``` with ```AggregateRootTrait```
* [x] **Repository**: implement your own based on ```RepositoryInterface```

## Event

### EventBus

* [x] **EventBus**: default implementation, implement your own based on ```EventBusInterface```

### EventListenerLocator

* [x] **EventListenerLocator**: based on convention, implement your own based on ```EventListenerLocatorInterface```

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

### How to implement you own event listener ?

Your must implement the ```EventListenerInterface```:
 
    class ConsoleOutputListener implements EventListenerInterface
    {
        /**
         * @var SystemLoggerInterface
         * @Flow\Inject
         */
        protected $systemLogger;
    
        /**
         * @param ButtonTagged $event
         * @param MessageMetadata $metadata
         */
        public function onButtonTagged(ButtonTagged $event, MessageMetadata $metadata)
        {
            $this->systemLogger->log('--- ConsoleOutputListener say something has been tagged ---');
        }
    }
    
The event handler locator can throw exception is something wrong with your command handler definition, please check your 
system log to have more informations.

All the wiring between event is done automatically, if you respect the following convention:

* Method name must be on[ShortEventName]
* The first parameter must be casted with your ```Event```
* The second parameter is optional, but should be casted to ```MessageMetadata```

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
