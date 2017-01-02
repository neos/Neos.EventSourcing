# Event Sourcing and CQRS for Flow Framework

_This package is currently under development and not fully working, please don't use it in production._

The goal of the project is to provide the infrastructure for ES/CQRS for applications based on Flow Framework.

# Requirements

* PHP 7
* Flow 4.0

# Configuration

To be able to send events to a different DB, the ES package uses its own DB connection. These are the defaults which you will need to override:
```
Neos:
  EventSourcing:
    EventStore:
      storage:
        options:
          eventTableName: neos_eventsourcing_eventstore_events
          backendOptions:
            driver: pdo_mysql
            host: 127.0.0.1
            dbname: null
            user: null
            password: null
            charset: utf8
```

If you want the package to use the same DB connection your main application uses and want to save on typing, you can use YAML path references to point the ES storage config to the main one.
```
Neos:
  Flow:
    persistence:
      # Add the "&mybackend" reference here (name it however you want, just keep the & in the beginning)
      backendOptions: &mybackened
        # Your usual DB credentials / config here [...]

  EventSourcing:
    EventStore:
      storage:
        options:
          backendOptions: *mybackend
```

# Packages

The features are split into different packages:

* **[Neos.EventSourcing](https://github.com/neos/Neos.EventSourcing)**: mostly infrastructure (interface, trait, abstract class) and the event/query bus
* **[Neos.EventSourcing.MonitoringHelper](https://github.com/neos/Neos.EventSourcing.MonitoringHelper)**: aspect to monitor performance of the ```Neos.EventSourcing``` infrastructure
* **[Neos.EventSourcing](https://github.com/neos/Neos.EventSourcing)**: event store to support event sourcing
* **[Neos.EventSourcing.InMemoryStorageAdapter](https://github.com/neos/Neos.EventSourcing.InMemoryStorageAdapter)**: in memory event storage, mainly for testing
* **[Neos.EventSourcing.DatabaseStorageAdapter](https://github.com/neos/Neos.EventSourcing.DatabaseStorageAdapter)**: doctrine dbal based event storage

More storage can be added later (Redis, ...).

# Folder Structure

This is a PSR-4 package structure:

    Your.Package/
      Application/
        Controller/
        Command/
        Service/
        Projection/
            [ProjectionName]/
                [ProjectionName]Finder.php
                [ProjectionName]Projector.php
                [ProjectionName].php

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

# Components

Currently most components are included in the ```Neos.EventSourcing``` package. In the future some components can be split into
separate packages for more flexibility.

## Command

### Command

    final class ConfirmOrder
    {
        /**
         * @var string
         */
        protected $identifier;

        /**
         * @param string $identifier
         * @param float $duration
         */
        public function __construct(string $identifier)
        {
            $this->identifier = $identifier;
        }

        /**
         * @return string
         */
        public function getIdentifier(): string
        {
            return $this->identifier;
        }
    }

### CommandHandler

    /**
     * @Flow\Scope("singleton")
     */
    class ButtonCommandHandler
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
            $button = new Button($command->getIdentifier(), $command->getPublicIdentifier());
            $button->changeLabel($command->getLabel());

            $this->buttonRepository->save($button);
        }

    }

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

An event class can also represent an event type from a remote system. The implementation is the same like a regular
local event, except that it is mapped to an event type which is not supported by the automatic event class to
event type mapping. Usually the event type identifier mapped to an event class follows the pattern
`PackageKey:ShortEventTypeName`. A class representing a remote event can explicitly provide a custom event type:

    final class SomethingHappenedElsewhere implements EventInterface, ProvidesEventTypeInterface
    {
        /**
         * @return string
         */
        static public function getEventType(): string
        {
            return 'NotAcme.SomeRemotePackage:SomethingHappened';
        }
        …
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
         * @param EventMetadata $metadata
         */
        public function onButtonTagged(ButtonTagged $event, EventMetadata $metadata)
        {
            $this->systemLogger->log('--- ConsoleOutputListener say something has been tagged ---');
        }
    }

The event handler locator can throw an exception if something is wrong with your command handler definition. In that
case please check your system log for more information.

All the wiring between event is done automatically, if you respect the following convention:

* Method name must be on[ShortEventName]
* The first parameter must be casted with your ```Event```
* The second parameter is optional, but should be casted to ```EventMetadata```

## EventStore

See package **Neos.EventSourcing**.

## Message

Messaging infrastructure, base class/traits to build your own Events.

## Projections

Because a projection is usually tailored to one or more views in an application, we recommend using the
`Application\Projection\[ProjectionName]` namespace for the respective code.

A projection usually consists of three classes: the Projector, the Finder and the Read Model.

### Projector

The projection is generated and updated by the Projector. The Projector listens to events it is interested in and
updates its projection accordingly.

The `add()`, `update()` and `remove()` methods are *protected*. Instead of manually adding, updating or removing
objects, these methods are called by event handler methods contained in the Projector.

### Finder

The Finder provides methods for querying and retrieving the state of the projection in the form of Read Models.
It is typically used in controllers and other similar parts of the application for querying the projection by using the
well-known `findBy*()` and `findOneBy` methods. In contrast to a Repository though, users cannot add, update or remove
objects.

### Read Model

A Read Model is a simple PHP class which is used for passing around the state of a projection. It is also called a
Data Transfer Object (DTO). The Finder will return its results as one or more Read Model instances.

### Doctrine-based projections

The easiest way to implement a projection is to extend the `AbstractDoctrineProjector` and `AbstractDoctrineFinder`
classes. Apart from the Projector and Finder, you also need a Read Model, which can be a plain PHP object.

The following Read Model is used in a projection for organizations. It has a few specialities which are explained
right after the code.

```php
namespace Acme\Application\Projection\Organization;

use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Neos\EventSourcing\Annotations as CQRS;

/**
 * General purpose Organization Read Model
 *
 * @Flow\Entity
 * @ORM\Table(name="acme_projection_organization_v2")
 */
class Organization
{

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @ORM\Id
     * @var string
     */
    public $identifier;

    /**
     * @var string
     */
    public $name;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    public $logoIdentifier;

    /**
     * @var array
     */
    public $projects = [];

    /**
     * @return AssetInterface|null
     */
    public function getLogo()
    {
        if ($this->logoIdentifier !== null) {
            return $this->assetRepository->findByIdentifier($this->logoIdentifier);
        }
        return null;
    }
}
```

Read Models currently need to be annotated with `@Flow\Entity`. At a later point it is planned to introduce a specific
annotation `@CQRS\ReadModel` for that, but that requires a core change in Flow.

It is best practice to manually set the database table name via the `@ORM\Table` annotation.

Like with Entities, injected properties or those marked with `@Flow\Transient` will be ignored by the persistence
mechanism.

You can define one or more properties which are used as the identifier (or a compound identifier) by using the
`@ORM\Id` annotation.

Property types are detected by Flow's Class Schema implementation. You can override this base configuration through
`@ORM\Column` annotations.

In general, properties are *public* for easier handling in code dealing with Read Models. Even if a user decides to
modify a property, it won't be persisted, because the `update()` method in the Projector can only be called by the
Projector itself. This Read Model provides a special getter for retrieving the `Asset` object of an organization's
logo.

A common use case for Read Models are Fluid templates: simply access any of the properties (including `logo`) by
passing the Read Model instance as a variable.

The database schema for these models / projections needs to be created with Flow's regular Doctrine migration mechanism.
That means: `./flow doctrine:migrationgenerate`, adjust and `./flow doctrine:migrate`.

The corresponding Projector class for this example projection could look like this:

```php
namespace Acme\Application\Projection\Organization;

use Acme\Domain\Aggregate\Organization\Event\OrganizationHasBeenCreated;
use Acme\Domain\Aggregate\Organization\Event\OrganizationHasBeenDeleted;
use Acme\Domain\Aggregate\Organization\Event\OrganizationLogoHasBeenChanged;
use Neos\EventSourcing\Projection\Doctrine\AbstractDoctrineProjector;
use TYPO3\Flow\Annotations as Flow;

/**
 * Organization Projector
 *
 * @Flow\Scope("singleton")
 */
class OrganizationProjector extends AbstractDoctrineProjector
{
    /**
     * @Flow\Inject
     * @var OrganizationFinder
     */
    protected $organizationFinder;

    /**
     * @param OrganizationHasBeenCreated $event
     * @return void
     */
    public function whenOrganizationHasBeenCreated(OrganizationHasBeenCreated $event)
    {
        $organization = new Organization();
        $this->mapEventToReadModel($event, $organization);
        $this->add($organization);
    }

    /**
     * @param \Acme\Domain\Aggregate\Organization\Event\OrganizationHasBeenDeleted $event
     * @return void
     */
    public function whenOrganizationHasBeenDeleted(OrganizationHasBeenDeleted $event)
    {
        $organization = $this->organizationFinder->findOneByIdentifier($event->getIdentifier());
        $this->remove($organization);
    }

    /**
     * @param \Acme\Domain\Aggregate\Organization\Event\OrganizationLogoHasBeenChanged $event
     * @return void
     */
    public function whenOrganizationLogoHasBeenChanged(OrganizationLogoHasBeenChanged $event)
    {
        $organization = $this->organizationFinder->findOneByIdentifier($event->getIdentifier());
        $organization->logoIdentifier = $event->getLogoIdentifier();
        $this->update($organization);
    }
}
```

The corresponding Finder class providing the query methods may look as simple as this:

```php
namespace Acme\Application\Projection\Organization;

use Neos\EventSourcing\Projection\Doctrine\AbstractDoctrineFinder;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\QueryInterface;

/**
 * Organization Finder
 *
 * @Flow\Scope("singleton")
 *
 * @method Organization findOneByIdentifier(string $identifier)
 * @method Organization findOneByName(string $name)
 * @method Organization findOneBySlug(string $slug)
 */
class OrganizationFinder extends AbstractDoctrineFinder
{
    /**
     * @var array
     */
    protected $defaultOrderings = [ 'name' => QueryInterface::ORDER_ASCENDING ];
}
``

... todo: more explanations

License
-------

Licensed under MIT, see [LICENSE](LICENSE)
