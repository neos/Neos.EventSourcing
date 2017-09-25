# Event Sourcing and CQRS for Flow Framework

_This package is currently under development and not fully tested, please don't use it in production._

The goal of the project is to provide the infrastructure to apply ES and an CQRS pattern for applications based on Flow Framework.

# DISCLAIMER

**Please note:** This documentation (as well as the inline docs) might not be up to date with the implementation of this package unfortunately.
If you find any errors, please let us know or directly create a PR.

Please bear with us in the meantime and [get in touch](https://www.neos.io/docs-and-support/support.html) if you have any questions!

# Requirements

* PHP 7
* Flow 4.0

# Recommended Project Structure

We highly recommend a PSR-4 folder structure and separating every `Bounded Context` into it's own package.
We have an ongoing discussion about how to structure the code here: https://github.com/neos/Neos.EventSourcing/issues/9
Our *current* recommendation (as of April 2017) for the structure is as follows:

    Your.BoundedContextPackage.Command/
      Classes/
        Controller/
          [UseCase]Controller.php
          …
        Service/
          [SomeDomain]Service.php
          …
        Model/
          [YourAggregate]/
            Command/
              [DoSomething].php
              …
            Event/
              [SomethingHappened].php
              …
            [YourAggregate].php
            [YourAggregate]CommandHandler.php
            [YourAggregate]Repository.php
          …
        Process/
          [Some]ProcessManager.php
          …

    Your.BoundedContextPackage.Query/
      Classes/
        Controller/
          [ReadUseCase]Controller.php
          …
        Dto/
          [SomeQueryDto].php
          …
        Service/
          [SomeNonDomain]Service.php
          …
        Projection/
          [ProjectionName]/
            [ProjectionName].php
            [ProjectionName]Finder.php
            [ProjectionName]Projector.php
          …

The reason to separate your Command and Query sides into own Packages is to make it clear those are fully separate
parts of your application. However, it is no problem to have both parts inside a single package.

Also note that this is just a recommendation and not a necessary structure for anything to work as expected.
The only requirements are the naming conventions for the Aggregate Repository and the Projection Finder and Projector.
Those can be easily overruled in the code through the respective properties ```aggregateClassName``` and ```readModelClassName``` though.

# Command / Write side

Your write side models your domain logic, ensuring consistency and keeping business rules intact.
It's public API is defined by the Commands and Events. Business constraints are commonly modelled through Aggregates and
processes through ProcessManagers.

There are many cases though where you can get along without the latter two and only end up with CommandHandlers that
receive Commands and emit Events (through the ```EventPublisher```).

## Core Domain

### Command

The command models a single very specific intention to change your application.

```php
final class ConfirmOrder
{
    /**
     * @var string
     * @Flow\Validate(type="Uuid")
     */
    protected $identifier;

    /**
     * @param string $identifier
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
```

Notice that the commands do not have any dependency on the framework. They are pure POJOs and should optimally be immutable.

Commands should be validated inside your Controller like normal Flow controller arguments to make sure they are
structurally valid. Further constraints can then be validated with Domain Model Validators (see [here](http://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartIII/Validation.html#validating-domain-models)) or directly inside the 
Controller or CommandHandler.

### Event

Events model a fact that something happened in your domain that is of interest. They should be fully self-contained
with all information that is necessary to derive a meaningful interpretation of the fact and be immutable.
Events need to implement the ```EventInterface``` marker Interface, which contains no methods, so you are free to focus on your domain.
The interface is used by Flow to provide infrastructure helpers (monitoring, debugging, ...).

```php
final class ProductedWasOrdered implements EventInterface
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
     * @param string $productIdentifier
     * @param integer $amount
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
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }
}
```

An event class can also represent an event type from a remote system. The implementation is the same like a regular
local event, except that it is mapped to an event type which is not supported by the automatic event class to
event type mapping. Usually the event type identifier mapped to an event class follows the pattern
`PackageKey:ShortEventTypeName`. A class representing a remote event can explicitly provide a custom event type:

```php
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
```

### CommandHandlers

```php
/**
 * @Flow\Scope("singleton")
 */
final class ButtonCommandHandler
{
    /**
     * @var ButtonRepository
     * @Flow\Inject
     */
    protected $buttons;

    /**
     * @param CreateButton $command
     */
    public function handleCreateButton(CreateButton $command)
    {
        $button = Button::initialize($command->getButtonIdentifier(), $command->getLabel());
        $this->buttons->add($button);
    }
}
```

The CommandHandler is currently not a formal part of the Framework, but is a recommended abstraction to introduce
in order to easily support dispatching commands through other entry points (e.g. CLI). It might be introduced (again)
later on in combination with an CommandBus to provide a single (asynchronous) dispatch entry point.

### Aggregates

Aggregates model hard business constraints that may not be violated at any time. They form a very strict consistency
boundary inside your domain and do not always need to represent single Entities.

Any violations of the hard business constraints should throw an Exception immediately.

```php
use Neos\EventSourcing\Domain\AbstractEventSourcedAggregateRoot;

class Project extends AbstractEventSourcedAggregateRoot
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $aggregateIdentifier
     */
    protected function __construct(string $aggregateIdentifier)
    {
        $this->identifier = $aggregateIdentifier;
    }

    /**
     * @param string $projectId
     * @param string $title
     * @return Project
     */
    static public function startNew(string $projectId, string $title): Project
    {
        $project = new static($projectId);
        $project->recordThat(new NewProjectWasStarted($projectId, $title));
        return $project;
    }

    /**
     * @param NewProjectWasStarted $event
     */
    public function whenNewProjectWasStarted(NewProjectWasStarted $event)
    {
        $this->identifier = $event->getProjectId();
    }
}
```

Notice how the constructor is made protected to avoid any accidental instanciation of the aggregate. Instead
a static factory method that describes the business intention for creating the aggregate.
The factory method then records an event that describes the fact that this aggregate was created and returns the new instance.

You should never record events inside your constructor, because then it would not be possible to instanciate the
Aggregate without changing it's history.

Also note how the according handler method for the `NewProjectWasStarted` event does not set a title property on
the aggregate, even though it is part of the event payload. Do not model a structural Entity in your domain, but
rather only what is needed to keep the constraints intact. The title is not part of any constraints that the
aggregate later needs to enforce, but it is still needed by the domain to correctly represent a `Project`.

### ProcessManagers

t.b.w.

### EventPublisher

You can use the EventPublisher directly to record and distribute events outside of your Aggregates. For that, just inject
it into your CommandHandler and emit new Domain Events there according to an incoming command which does not touch
any hard business constraints that need to be enforced by an Aggregate.

In order to provide additional metadata for the event (a simple array with keys and values), you can wrap the event into
a `EventWithMetadata` object and pass that wrapper to the Event Publisher's publish function.

# Query / Read side

Your read side is mainly made up of one or multiple Projections and all the Application logic around them,
e.g. the typical Flow MVC components like Controller(s), DTOs, Templates and Layouts.

## Projections

A projection usually consists of three classes: the Projector, the Finder and the Read Model.

### Projector

The projection is generated and updated by the Projector. The Projector listens to events it is interested in and
updates its projection accordingly.

The `add()`, `update()` and `remove()` methods are *protected*. Instead of manually adding, updating or removing
objects, these methods are called by event handler methods contained in the Projector.

#### Asynchronous projections / AsynchronousEventListenerInterface

t.b.w.

### Finder

The Finder provides methods for querying and retrieving the state of the projection in the form of Read Models.
It is typically used in controllers and other similar parts of the application for querying the projection by using the
well-known `findBy*()` and `findOneBy` methods. In contrast to a Repository though, users cannot add, update or remove
objects.

> Note: Never use the Finder inside your Projector to query your projection state, only use the methods provided by the Projector itself.

### Read Model

A Read Model is a simple PHP class which is used for passing around the state of a projection. It is also called a
Data Transfer Object (DTO). The Finder will return its results as one or more Read Model instances. The Projector
will update the projection state as one or more Read Model instances.

### Doctrine-based projections

The easiest way to implement a projection is to extend the `AbstractDoctrineProjector` and `AbstractDoctrineFinder`
classes. Apart from the Projector and Finder, you also need a Read Model, which can be a plain PHP object.

The following Read Model is used in a projection for organizations. It has a few specialities which are explained
right after the code.

```php
namespace Acme\Crm\Query\Projection\Organization;

use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Flow\Annotations as Flow;
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

    public function __construct(string $identifier, string $name)
    {
        $this->identifier = $identifier;
        $this->name = $name;
    }

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
namespace Acme\Crm\Query\Projection\Organization;

use Acme\Crm\Command\Model\Organization\Event\OrganizationHasBeenCreated;
use Acme\Crm\Command\Model\Organization\Event\OrganizationHasBeenDeleted;
use Acme\Crm\Command\Model\Organization\Event\OrganizationLogoHasBeenChanged;
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
     * @param OrganizationHasBeenCreated $event
     * @return void
     */
    public function whenOrganizationHasBeenCreated(OrganizationHasBeenCreated $event)
    {
        $organization = new Organization($event->getOrganizationIdentifier(), $event->getName());
        $this->add($organization);
    }

    /**
     * @param OrganizationHasBeenDeleted $event
     * @return void
     */
    public function whenOrganizationHasBeenDeleted(OrganizationHasBeenDeleted $event)
    {
        $organization = $this->get($event->getOrganizationIdentifier());
        $this->remove($organization);
    }

    /**
     * @param OrganizationLogoHasBeenChanged $event
     * @return void
     */
    public function whenOrganizationLogoHasBeenChanged(OrganizationLogoHasBeenChanged $event)
    {
        $organization = $this->get($event->getOrganizationIdentifier());
        $organization->logoIdentifier = $event->getLogoIdentifier();
        $this->update($organization);
    }
}
```

The corresponding Finder class providing the query methods may look as simple as this:

```php
namespace Acme\Crm\Query\Projection\Organization;

use Neos\EventSourcing\Projection\Doctrine\AbstractDoctrineFinder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryInterface;

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
```

### How to implement you own event listener ?

To implement other custom event listeners you only need to implement the `EventListenerInterface`:

```php
class ConsoleOutputListener implements EventListenerInterface
{
    /**
     * @var SystemLoggerInterface
     * @Flow\Inject
     */
    protected $systemLogger;

    /**
     * @param ButtonWasTagged $event
     * @param EventMetadata $metadata
     */
    public function whenButtonWasTagged(ButtonWasTagged $event, RawEvent $rawEventData)
    {
        $this->systemLogger->log("--- The button {$event->getButtonIdentifer()} was tagged {$event->getTagName()} ---");
    }
}
```

All the wiring between event and event listeners is done automatically, if you respect the following convention:

* Method name must be when[ShortEventName]
* The first parameter must be of type `EventInterface` and is your concrete DomainEvent instance
* The second parameter is optional and is of type `RawEvent`, containing the raw data and metadata from the EventStore

If these conventions are violated an exception will be thrown during compile time. In that case please check your
system log for more information.

Also, you can optionally let your `EventListener` implement `ActsBeforeInvokingEventListenerMethodsInterface`
in order to receive a hook method `beforeInvokingEventListenerMethod` before the concrete event handling method (`when*`) is called.

License
-------

Licensed under MIT, see [LICENSE](LICENSE)
