# CRQS for Flow Framework

_This package is currently under development and not fully working, please don't use it in production._

The goal of the project is to provide infrastructure to support CQRS/ES project based on Flow Framework

# Requirements

* PHP 7
* Flow 3.3 LTS

# Packages

The features are splitted in differents packages:

* **[Neos.Cqrs](https://github.com/neos/Neos.Cqrs)**: mostly infrastructure (interface, trait, abstract class) and the event/query bus
* **[Neos.Cqrs.MonitoringHelper](https://github.com/neos/Neos.Cqrs.MonitoringHelper)**: aspect to monitor performance of the ```Neos.Cqrs``` infrastructure
* **[Neos.EventStore](https://github.com/neos/Neos.EventStore)**: event store to support event sourcing
* **[Neos.EventStore.InMemoryStorageAdapter](https://github.com/neos/Neos.EventStore.InMemoryStorageAdapter)**: in memory event storage, mainly for testing
* **[Neos.EventStore.DatabaseStorageAdapter](https://github.com/neos/Neos.EventStore.DatabaseStorageAdapter)**: doctrine dbal based event storage

More storage can be added later (Redis, ...).

# Folder Structure

This is a PSR4 package structure:
 
    Your.Package/
      Application/
        Controller/
        Command/
        Service/
      
      CommandHandler/
      
      Domain/
        Service/
        Aggregate/
          [YourAggregate]/
            Command/
            Event/
            Service/
            YourAggregate.php
            YourAggregateRepository.php
      
      EventListener/
          
      Query/
        [WhatYouNeed]/
            [WhatYouNeed]Projection.php
            [WhatYouNeed]ReadModel.php
            [WhatYouNeed]Finder.php

# Components

Currently most components are included in the ```Neos.Cqrs``` package. In the future some component can be splitted 
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

See package **Neos.EventStore**.

## Message

Messaging infrastructure, base class/traits to build your own Events.

## Query

[not documented, work in progress]

License
-------

Licensed under MIT, see [LICENSE](LICENSE)
