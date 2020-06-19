# Tutorial

For this tutorial we want to build a simple *notification* system.
It will allow short text messages to be sent to users that they have to acknowledge in time.

Event-Sourcing will potentially give us some advantages in this scenario:
* A reliable audit log in cases of errors (did user x see notification y before incident y happened?)
* Non-blocking (async) handling
* Temporal modeling

Let's assume we have the following 3 milestones:

> ### Milestone 1:
> * **Notifications** can be sent to individual **Users**
> * Users can see assigned Notifications in their **Inbox** and can **acknowledge** those
>
> ### Milestone 2:
> * **Channels** can be introduced in order to notify multiple Users at once
> * Users can subscribe to Channels to receive corresponding Notifications
>
> ### Milestone 3:
> * Flood protection: Don't allow more than one Notification to be sent to a Channel within *10 seconds*
> * If a User doesn't acknowledge a Notification within *1 hour* they receive a **Reminder** email
> * Reminders are aggregated, so that no more than one mail per *10 minutes* is sent to one user

## Setup

For this tutorial we assume you have a running Flow 5.3 (or newer) installation with the `neos/event-sourcing` package installed in version 2.x.
To keep things easy, we'll put all the code into one Flow package `Acme.Notifications`.

### Create package

You can create it using the `package:create` CLI command:

```
./flow package:create Acme.Notifications
```

This should create a new folder at `DistributionPackages/Acme.Notifications` and install the package.

### Fix dependencies

Now you have to add a dependency to the `neos/event-sourcing` package, by adding the following line to its composer
manifest (at `DistributionPackages/Acme.Notifications/composer.json`):

```json
{
    ...
    "require": {
        ...
        "neos/event-sourcing": "^2.0"
    }
    ...
}
```

Afterwards make sure to rescan all packages so that the new dependencies take effect:

```
./flow flow:package:rescan
```

### Configure Event Store

The `neos/event-sourcing` package no longer comes with a pre-configured Event Store, but it's just a matter of a few lines of
YAML to configure a custom store.

Put those lines into a new file `DistributionPackages/Acme.Notifications/Configuration/Settings.yaml` to configure an Event Store
instance that is called "Acme.Notifications:EventStore".

*Note:* The name is arbitrary, but it's good practice prefixing it with a package key in order to prevent naming clashes

*Configuration/Settings.yaml:*
```yaml
Neos:
  EventSourcing:
    EventStore:
      stores:
        'Acme.Notifications:EventStore':
          storage: 'Neos\EventSourcing\EventStore\Storage\Doctrine\DoctrineEventStorage'
          storageOptions:
            eventTableName: 'acme_notifications_events'
```

To verify that the event store is set up correctly, you can use the `status` command:

```
./flow eventstore:status
```

And the output should be something like this:

```
Displaying status information for 1 Event Store backend(s):

Event Store "Acme.Notifications:EventStore"
-------------------------------------------------------------------------------
Host: 127.0.0.1
Port:
Database: some_db_name
Driver: pdo_mysql
Username: some_db_user
Table: acme_notifications_events (missing) !!!
```

<details><summary>:information_source:&nbsp; <b>Note...</b></summary>

> If the above command leads to an exception, you might have to flush the caches via
> ./flow flow:cache:flush
</details>

As you can see, the `DoctrineEventStorage` uses the same connection settings that are configured
for flow by default.
The table for the events does not exist yet and you can create it via:

```
./flow eventstore:setup Acme.Notifications:EventStore
```

This should lead to the following output

```
Setting up Event Store "Acme.Notifications:EventStore"
: Creating database table "acme_notifications_events" in database "some_db_name" on host 127.0.0.1....
: ...
SUCCESS
```

Congratulations, you have just set up your first Event Store :)

## Events

With the Milestones above the following [:book: Domain Events](Glossary.md#domain-event) could occur:

* A User was notified
* A Notification was acknowledged by the User
* A Channel was added
* A User subscribed to a Channel
* A Notification was sent to a Channel
* A Reminder Email was sent

For a final version there would probably be more Events (there is currently no way to *unsubscribe* users from a Channel for example). But for the sake of simplicity we stick to the six above for this tutorial.

## Milestone 1

> * **Notifications** can be sent to individual **Users**
> * Users can see assigned Notifications in their **Inbox** and can **acknowledge** those

In this package a Domain Event is represented by a class that implements the `Neos\EventSourcing\Event\DomainEventInterface`.
So let's create a first Event class:

*Classes/Events/UserWasNotified.php:*
```php
<?php
declare(strict_types=1);
namespace Acme\Notifications\Events;

use Neos\EventSourcing\Event\DomainEventInterface;

final class UserWasNotified implements DomainEventInterface
{

    /**
     * @var string
     */
    private $userId;

    /**
     * @var string
     */
    private $notificationId;

    /**
     * @var string
     */
    private $message;

    /**
     * @var \DateTimeImmutable
     */
    private $timestamp;

    public function __construct(string $userId, string $notificationId, string $message, \DateTimeImmutable $timestamp)
    {
        $this->userId = $userId;
        $this->notificationId = $notificationId;
        $this->message = $message;
        $this->timestamp = $timestamp;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getNotificationId(): string
    {
        return $this->notificationId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }
}
```

<details><summary>:information_source:&nbsp; <b>Note...</b></summary>

> All events have a "recordedAt" metadata that tracks the timestamp at which the event was *committed* to the Event Store
> Since we need the timestamp at which the event *occurred* originally, we add an additional DateTime property to the event itself
</details>

To test the application we create a simple `CommandController` so that we can interact with it via CLI:

*Classes/Command/UserCommandController.php:*
```php
<?php
declare(strict_types=1);
namespace Acme\Notifications\Command;

use Acme\Notifications\Events\UserWasNotified;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Utility\Algorithms;

final class UserCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var EventStoreFactory
     */
    protected $eventStoreFactory;

    /**
     * Notifies a user with <user-id> of the specified <message>
     *
     * @param string $userId ID of the user to notify
     * @param string $message Message to send to the user
     */
    public function notifyCommand(string $userId, string $message): void
    {
        // generate a unique id for the notification
        $notificationId = Algorithms::generateUUID();
        $now = new \DateTimeImmutable();
        $event = new UserWasNotified($userId, $notificationId, $message, $now);

        // we publish user related events to a stream named "user-<user-id>"
        $streamName = StreamName::fromString('user-' . $userId);
        $eventStore = $this->eventStoreFactory->create('Acme.Notifications:EventStore');
        $eventStore->commit($streamName, DomainEvents::withSingleEvent($event));

        $this->outputLine('<success>Sent notification <b>%s</b> to user <b>%s</b>.</success>', [$notificationId, $userId]);
    }
}
```

With that in place you are able to publish the first event:

```
./flow user:notify user1 "The first message"
```

And the output should be something like:

```
Sent notification fdf70c75-daa5-46d5-8cae-a2290e290d79 to user user1.
```

<details><summary>:information_source:&nbsp; <b>Note...</b></summary>

> We just assume that a user with the id "user1" exists at this point. User management ist out of scope of this tutorial
</details>

When using the default Event Store configuration, the following row should have been added to the `acme_notifications_events` database table:

|sequencenumber|stream    |version|type                              |payload                                                                                                                    |metadata|id                                  |correlationidentifier|causationidentifier|recordedat         |
|--------------|----------|-------|----------------------------------|---------------------------------------------------------------------------------------------------------------------------|--------|------------------------------------|---------------------|-------------------|-------------------|
|1             |user-user1|0      |Acme.Notifications:UserWasNotified|{↵    "userId": "user1",↵    "notificationId": "28a5c887-25d9-4258-887c-b1dd614a4e57",↵    "message": "The first message"↵}|[]      |875378c5-e808-4649-a51e-95a6c39a88e5|NULL                 |NULL               |2019-12-13 17:33:14|


## Projection

In Event-Sourced systems the application state is stored as a sequence of events.
This state can be [:book: projected](Glossary.md#projection) into a form that is optimized for *reading*, the so called [:book: Read Model](Glossary.md#read-model) (aka "Query Model" or "View Model").

For the projector we have to implement the `Neos\EventSourcing\Projection\ProjectorInterface`.
Like any [:book: Event Listener](Glossary.md#event-listener) the events are handled by corresponding `when<EventClassName>()` methods: 

*Classes/InboxProjector.php:*
```php
<?php
declare(strict_types=1);
namespace Acme\Notifications;

use Acme\Notifications\Events\UserWasNotified;
use Neos\EventSourcing\Projection\ProjectorInterface;

final class InboxProjector implements ProjectorInterface
{

    public function reset(): void
    {
        // TODO: reset the projector state, will be invoked when the projection is replayed
    }

    public function whenUserWasNotified(UserWasNotified $event): void
    {
        // TODO: update the projector state
    }
}
```

Since you can run multiple event stores in one installation you'll have to "register" this projector as listener.
This can be done in the event store configuration:

*Configuration/Settings.yaml:*
```yaml
Neos:
  EventSourcing:
    EventStore:
      stores:
        'Acme.Notifications:EventStore':
          #...
          listeners:
            'Acme\Notifications\.*': true
```

With this configuration in place, all classes underneath the namespace `\Acme\Notifications\` that implement the `EventListenerInterface` or `ProjectorInterface` are
considered event listeners for our event store.

You can test the setup by listing all projections via the `projection:list` command:

```
./flow projection:list
```

```
There is one projection configured:

PACKAGE "ACME.NOTIFICATIONS":
-------------------------------------------------------------------------------
  inbox                                    Acme\Notifications\InboxProjector
```

### Projection state

In this case we want to persist the state of the `Inbox` Projection in a simple database table.
So we'll need a corresponding Doctrine migration:

*Migrations/Mysql/Version20200617175816.php*:
```php
<?php
declare(strict_types=1);
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200617175816 extends AbstractMigration
{

    public function getDescription()
    {
        return 'Table for the inbox Read Model';
    }

    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE acme_notifications_inbox (notification_id VARCHAR(40) NOT NULL, user_id VARCHAR(40) NOT NULL, message TEXT NOT NULL, timestamp DATETIME NOT NULL, PRIMARY KEY (notification_id, user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE acme_notifications_inbox');
    }
}
```

```
./flow doctrine:migrate
```

<details><summary>:information_source:&nbsp; <b>Tip...</b></summary>

> As we don't want to create Entity classes in this tutorial and just work with DBAL, we can disable automatic Doctrine migrations for it with the following settings:

```yaml
Neos:
  Flow:
    persistence:
      doctrine:
        migrations:
          ignoredTables:
            '^acme_notifications_.*': true
```
</details>

Otherwise future calls of `./flow doctrine:migrationgenerate` will create a migration that drops the `acme_notifications` table because no corresponding entity can be found.
</details>

Now we can extend our `InboxProjector` to actually make use of the new table:

*Classes/InboxProjector.php:*
```php
<?php
declare(strict_types=1);
namespace Acme\Notifications;

use Acme\Notifications\Events\UserWasNotified;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Neos\EventSourcing\Projection\ProjectorInterface;

final class InboxProjector implements ProjectorInterface
{
    /**
     * @var Connection
     */
    protected $dbal;

    public function injectEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->dbal = $entityManager->getConnection();
    }

    public function reset(): void
    {
        $this->dbal->executeQuery('TRUNCATE TABLE acme_notifications_inbox');
    }

    public function whenUserWasNotified(UserWasNotified $event): void
    {
        $this->dbal->insert('acme_notifications_inbox', [
            'notification_id' => $event->getNotificationId(),
            'user_id' => $event->getUserId(),
            'message' => $event->getMessage(),
            'timestamp' => $event->getTimestamp()
        ], [
            'timestamp' => Types::DATETIME_IMMUTABLE,
        ]);
    }
}
```

From now on the `InboxProjector` will be triggered automatically whenever an event with a matching `when*()` method is committed to the Event Store.
Additionally, we can *replay* the projection to apply events that have been published in the past:


```
./flow projection:replay inbox
```

With the one event we published before this should lead to the following output:

```
Replaying events for projection "acme.notifications:inbox" ...
    1 [============================]
Replayed 1 event(s).
```

Afterwards the `acme_notifications` table should contain one row:

|notification_id                     |user_id|message          |timestamp          |
|------------------------------------|-------|-----------------|-------------------|
|fdf70c75-daa5-46d5-8cae-a2290e290d79|user1  |The first message|2019-10-07 09:42:48|

To allow the application to query the Read Model we create a corresponding Finder:

*Classes/Inbox.php:*
```php
<?php
declare(strict_types=1);
namespace Acme\Notifications;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

final class Inbox
{
    /**
     * @var string
     */
    private $userId;

    /**
     * @var Connection
     */
    private $dbal;

    public function injectEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->dbal = $entityManager->getConnection();
    }

    protected function __construct(string $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Create an inbox instance for the given user id
     *
     * @param string $userId
     * @return static
     */
    public static function forUser(string $userId): self
    {
        return new static($userId);
    }

    /**
     * Get all pending notifications
     *
     * @return array
     */
    public function getNotifications(): array
    {
        return $this->dbal->fetchAll('SELECT * FROM acme_notifications_inbox WHERE user_id = :user_id', ['user_id' => $this->userId]);
    }
}
```

To use this we can add a second command to our `UserCommandHandler`:

*Classes/Command/UserCommandController.php:*
```php
<?php
// ...
use Acme\Notifications\Inbox;

final class UserCommandHandler extends CommandController
{

    // ...

    /**
     * List pending notifications for user <user-id>
     *
     * @param string $userId ID of the user to show pending notifications for
     */
    public function inboxCommand(string $userId): void
    {
        $notifications = Inbox::forUser($userId)->getNotifications();
        $this->outputLine('<b>%d</b> pending message(s) for user <b>%s</b>:', [count($notifications), $userId]);
        foreach ($notifications as $notification) {
            $this->outputLine('* <b>%s</b> (id: %s)', [$notification['message'], $notification['notification_id']]);
        }
    }
}

```

```
./flow user:inbox user1
```

```
1 pending message(s) for user user1:
* The first message (id: fdf70c75-daa5-46d5-8cae-a2290e290d79)
```

Finally, users should be able to *acknowledge* Notifications to hide them from the Inbox.

We start with the event again:

*Classes/Events/UserHasAcknowledgedNotification.php:*
```php
<?php
declare(strict_types=1);
namespace Acme\Notifications\Events;

use Neos\EventSourcing\Event\DomainEventInterface;

final class UserHasAcknowledgedNotification implements DomainEventInterface
{

    /**
     * @var string
     */
    private $userId;

    /**
     * @var string
     */
    private $notificationId;

    public function __construct(string $userId, string $notificationId)
    {
        $this->userId = $userId;
        $this->notificationId = $notificationId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getNotificationId(): string
    {
        return $this->notificationId;
    }
}
```

...and extend the `InboxProjector`:

*Classes/InboxProjector.php:*
```php
// ...
use Acme\Notifications\Events\UserHasAcknowledgedNotification;

final class InboxProjector implements ProjectorInterface
{
    // ...

    public function whenUserHasAcknowledgedNotification(UserHasAcknowledgedNotification $event): void
    {
        $this->dbal->delete('acme_notifications_inbox', ['notification_id' => $event->getNotificationId(), 'user_id' => $event->getUserId()]);
    }
}
```

You might be surprised that we just delete notifications from the database once they are acknowledged. But that's one of the advantages of Event-Sourcing:
Since the *Unique Source Of Truth* lies not in the Read Model but in the Events we can extend the projection at a later point (introducing a "Notification archive" for example).

In order to test Notification acknowledgment we can extend our `UserCommandController`:

*Classes/Command/UserCommandController.php:*
```php
// ...
use Acme\Notifications\Events\UserHasAcknowledgedNotification;

final class UserCommandController extends CommandController
{

    //...

    /**
     * Marks notification with <notification-id> acknowledged for user with <user-id>
     *
     * @param string $userId ID of the acknowledging user
     * @param string $notificationId ID of the notification to acknowledge
     */
    public function acknowledgeCommand(string $userId, string $notificationId): void
    {
        $event = new UserHasAcknowledgedNotification($userId, $notificationId);
        $streamName = StreamName::fromString('user-' . $userId);
        $eventStore = $this->eventStoreFactory->create('Acme.Notifications:EventStore');
        $eventStore->commit($streamName, DomainEvents::withSingleEvent($event));
        $this->outputLine('<success>Notification <b>%s</b> was acknowledged.</success>', [$notificationId]);
    }
}
```

### Consistency/Integrity

With the current state it would be possible to acknowledge Notifications that don't exist or have been acknowledged before.
This would not be a major issue with the current implementation: The "delete" clause in the projector would just not have any effect in this case.
But the recorded Domain Events would not reflect what actually happened and future Event Listeners could stumble upon this case.

To prevent non-existing and already acknowledged Notifications from being processed by the new command, we can simply check whether a Notification with the given id is in the Inbox for the respective user.
To do this we extend the `Inbox`:

*Classes/Inbox.php:*
```php
// ...
final class Inbox
{
    // ...

    /**
     * Returns TRUE if a notification with the specified id is pending, otherwise FALSE
     * 
     * @param string $notificationId
     * @return bool
     */
    public function containsNotification(string $notificationId): bool
    {
        return $this->dbal->fetchColumn('SELECT id FROM acme_notifications_inbox WHERE notification_id = :notification_id AND user_id = :user_id', ['user_id' => $this->userId, 'notification_id' => $notificationId]) !== false;
    }
}
```

And add a safe guard to the corresponding command:

*Classes/Command/UserCommandController.php:*
```php
// ...
final class UserCommandController extends CommandController
{

    public function acknowledgeCommand(string $userId, string $notificationId): void
    {
        $inbox = Inbox::forUser($userId);
        if (!$inbox->containsNotification($notificationId)) {
            $this->outputLine('<error>No notification with id <b>%s</b> pending for user <b>%s</b></error>', [$notificationId, $userId]);
            $this->quit(1);
            return;
        }
        // ...
    }
}
```

To test this you can run the new `user:acknowledge` command with a non existing notification id:

```
./flow user:acknowledge user1 non-existing-id
```

This should not commit any events and output an error message instead:
```
No notification with id non-existing-id pending for user user1
```

If you use an existing notification id, the output is
```
Notification <notification-id> was acknowledged.
```
a new `UserHasAcknowledgedNotification` event is recorded and the corresponding notification should be removed from the `acme_notifications_inbox` database table. 

## Milestone 2

> * **Channels** can be introduced in order to notify multiple Users at once
> * Users can subscribe to Channels to receive corresponding Notifications

As before let's start by creating the Domain Event classes:

<details><summary><code>Classes/Events/ChannelWasAdded.php</code></summary>

```php
<?php
declare(strict_types=1);
namespace Acme\Notifications\Events;

use Neos\EventSourcing\Event\DomainEventInterface;

final class ChannelWasAdded implements DomainEventInterface
{

    /**
     * @var string
     */
    private $channelId;

    /**
     * @var string
     */
    private $label;

    public function __construct(string $channelId, string $label)
    {
        $this->channelId = $channelId;
        $this->label = $label;
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
```
</details>

<details><summary><code>Classes/Events/UserWasSubscribedToChannel.php</code></summary>

```php
<?php
declare(strict_types=1);
namespace Acme\Notifications\Events;

use Neos\EventSourcing\Event\DomainEventInterface;

final class UserWasSubscribedToChannel implements DomainEventInterface
{

    /**
     * @var string
     */
    private $userId;

    /**
     * @var string
     */
    private $channelId;

    public function __construct(string $userId, string $channelId)
    {
        $this->userId = $userId;
        $this->channelId = $channelId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }
}
```
</details>

Now we can create another `CommandController` to test the app:

*Classes/Command/ChannelCommandController.php:*
```php
<?php
declare(strict_types=1);
namespace Acme\Notifications\Command;

use Acme\Notifications\Events\ChannelWasAdded;
use Acme\Notifications\Events\ChannelWasNotified;
use Acme\Notifications\Events\UserWasSubscribedToChannel;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Utility\Algorithms;

final class ChannelCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var EventStoreFactory
     */
    protected $eventStoreFactory;

    /**
     * Adds a new Channel for users to subscribe to
     *
     * @param string $channelId ID of the channel to add
     * @param string $label Human readable label of the channel
     */
    public function addCommand(string $channelId, string $label): void
    {
        $event = new ChannelWasAdded($channelId, $label);
        $streamName = StreamName::fromString('channel-' . $channelId);
        $eventStore = $this->eventStoreFactory->create('Acme.Notifications:EventStore');
        $eventStore->commit($streamName, DomainEvents::withSingleEvent($event));
        $this->outputLine('<success>Channel "%s" was created with id "<b>%s</b>"</success>', [$label, $channelId]);
    }

    /**
     * Subscribes user <user-id> to the channel <channel-id>
     *
     * @param string $channelId ID of the channel to subscribe the user to
     * @param string $userId ID of the user to subscribe
     */
    public function subscribeCommand(string $channelId, string $userId): void
    {
        $event = new UserWasSubscribedToChannel($userId, $channelId);
        $streamName = StreamName::fromString('channel-' . $channelId);
        $eventStore = $this->eventStoreFactory->create('Acme.Notifications:EventStore');
        $eventStore->commit($streamName, DomainEvents::withSingleEvent($event));
        $this->outputLine('<success>Subscribed user with id "%s" to channel <b>%s</b></success>', [$userId, $channelId]);
    }

    /**
     * Notifies all users subscribed to <channel-id>
     *
     * @param string $channelId ID of the channel to send a message to
     * @param string $message Message to send to all subscribed users
     */
    public function notifyCommand(string $channelId, string $message): void
    {
        $notificationId = Algorithms::generateUUID();
        $now = new \DateTimeImmutable();
        $event = new ChannelWasNotified($channelId, $notificationId, $message, $now);
        $streamName = StreamName::fromString('channel-' . $channelId);
        $eventStore = $this->eventStoreFactory->create('Acme.Notifications:EventStore');
        $eventStore->commit($streamName, DomainEvents::withSingleEvent($event));
        $this->outputLine('<success>Sent notification <b>%s</b> to channel <b>%s</b></success>', [$notificationId, $channelId]);
    }
}
```

<details><summary>:information_source:&nbsp; <b>Note...</b></summary>

> It's not very common to let the client define the ID of entities. For the sake of this example we consider it OK that the channel ID is defined via CLI.
> Instead we could for example create it in the Command Controller using a UUID, like we did for the Notification ID
</details>

This will already allow us to create new Channels and subscribe Users to them.
But there are no corresponding [:book: Event Listeners](Glossary.md#event-listener) for the new [:book: Domain Events](Glossary.md#domain-event), so this won't have any effect just yet.

You might be tempted to extend our existing `InboxProjector` to handle the logic of Channel subscription and notification, but this would increase complexity of the projector.
Instead we could consider the Channel management to belong to a separate [:book: Bounded Context](Glossary.md#bounded-context) that only interacts with the Notification system.

Another temptation might be to create a Read Model that tracks Channel subscriptions and then iterate through all subscribed users in the Command Controller:

```php
// ...
final class ChannelCommandController extends CommandController
{

    // ...

    public function notifyCommand(string $channelId, string $message): void
    {
        $userIds = $this->retrieveAssignedUserIdsFromSomeReadModel($channelId);
        foreach ($userIds as $userId) {
            // publish UserWasNotified event
        }
    }
}
```

This would certainly be an option, but there are some potential drawbacks:
* For thousands of subscribed users this might be rather slow and because the command handling is blocking, the invoking script would have to wait until all events have been published
* More importantly: If the script fails or is interrupted half-way through, it leaves us with an invalid state that is hard to recover from

Instead we could consider the "Channel notification" itself a Domain Event and process it asynchronously via a [:book: Process Manager](Glossary.md#process-manager).

So let's create an Event Class `ChannelWasNotified` that is very similar to the existing `UserWasNotified`:


<details><summary><code>Classes/Events/ChannelWasNotified.php</code></summary>

```php
<?php
declare(strict_types=1);
namespace Acme\Notifications\Events;

use Neos\EventSourcing\Event\DomainEventInterface;

final class ChannelWasNotified implements DomainEventInterface
{

    /**
     * @var string
     */
    private $channelId;

    /**
     * @var string
     */
    private $notificationId;

    /**
     * @var string
     */
    private $message;

    /**
     * @var \DateTimeImmutable
     */
    private $timestamp;

    public function __construct(string $channelId, string $notificationId, string $message, \DateTimeImmutable $timestamp)
    {
        $this->channelId = $channelId;
        $this->notificationId = $notificationId;
        $this->message = $message;
        $this->timestamp = $timestamp;
    }

    public function getChannelId(): string
    {
        return $this->channelId;
    }

    public function getNotificationId(): string
    {
        return $this->notificationId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }
}
```
</details>

And make sure that the event is published in the `CommandController`:

*Classes/Command/ChannelCommandController.php:*
```php
// ...
use Acme\Notifications\Events\ChannelWasNotified;

final class ChannelCommandController extends CommandController
{
    use Neos\Flow\Utility\Algorithms;
    use Acme\Notifications\Events\ChannelWasNotified;

    // ...

    /**
     * Notifies all users subscribed to <channel-id>
     *
     * @param string $channelId ID of the channel to send a message to
     * @param string $message Message to send to all subscribed users
     */
    public function notifyCommand(string $channelId, string $message): void
    {
        $notificationId = Algorithms::generateUUID();
        $now = new \DateTimeImmutable();
        $event = new ChannelWasNotified($channelId, $notificationId, $message, $now);
        $streamName = StreamName::fromString('channel-' . $channelId);
        $this->eventStore->commit($streamName, DomainEvents::withSingleEvent($event));
        $this->outputLine('<success>Sent notification <b>%s</b> to channel <b>%s</b></success>', [$notificationId, $channelId]);
    }
}
```

Now to the interesting part, the Process Manager. It is similar to a Projector in the sense that it reacts to Domain Events and can have its own state, but in contrary to a regular Projector the Process Manager can also trigger Commands.
Let's start with the state part.
We want to keep track of User-to-channel-subscriptions in a new database table that we need another Doctrine migration for:

```php
<?php
declare(strict_types=1);
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200617175826 extends AbstractMigration
{

    public function getDescription()
    {
        return 'Table for the user to channel association';
    }

    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE acme_notifications_channel_users (channel_id VARCHAR(40) NOT NULL, user_ids TEXT NOT NULL, PRIMARY KEY (channel_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE acme_notifications_channel_users');
    }
}
```

<details><summary>:information_source:&nbsp; <b>Note...</b></summary>

> For the sake of simplicity, we store the user ids as a comma separated list
</details>

*Classes/ChannelNotificationProcessManager.php:*
```php
<?php
declare(strict_types=1);
namespace Acme\Notifications;

use Acme\Notifications\Events\ChannelWasAdded;
use Acme\Notifications\Events\ChannelWasNotified;
use Acme\Notifications\Events\UserWasSubscribedToChannel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Neos\EventSourcing\EventListener\EventListenerInterface;

final class ChannelNotificationProcessManager implements EventListenerInterface
{
    /**
     * @var Connection
     */
    private $dbal;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->dbal = $entityManager->getConnection();
    }

    public function whenChannelWasAdded(ChannelWasAdded $event): void
    {
        $this->dbal->insert('acme_notifications_channel_users', ['channel_id' => $event->getChannelId(), 'user_ids' => '']);
    }

    public function whenUserWasSubscribedToChannel(UserWasSubscribedToChannel $event): void
    {
        $userIds = $this->usersThatSubscribedToChannel($event->getChannelId());
        $userIds[] = $event->getUserId();
        $this->dbal->update('acme_notifications_channel_users', ['user_ids' => implode(',', $userIds)], ['channel_id' => $event->getChannelId()]);
    }

    public function whenChannelWasNotified(ChannelWasNotified $event): void
    {
        $userIds = $this->usersThatSubscribedToChannel($event->getChannelId());
        foreach ($userIds as $userId) {
            // TODO forward the notification to user with id $userId
        }
    }

    /**
     * @param string $channelId
     * @return string[] array of user IDs that are subscribed to the specified channel
     */
    private function usersThatSubscribedToChannel(string $channelId): array
    {
        try {
            $userIdsString = $this->dbal->fetchColumn('SELECT user_ids FROM acme_notifications_channel_users WHERE channel_id = :channel_id', ['channel_id' => $channelId]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Could not fetch users for channel "%s": %s', $channelId, $e->getMessage()), 1563727265, $e);
        }
        if ($userIdsString === false || $userIdsString === '') {
            return [];
        }
        return explode(',', (string)$userIdsString);
    }
}
```

To publish the `UserWasNotified` events in the `whenChannelWasNotified`-handler we can copy the existing code from `UserCommandController::notifyCommand()`:

*Classes/ChannelNotificationProcessManager.php:*
```php
// ...
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Annotations as Flow;

final class ChannelNotificationProcessManager implements EventListenerInterface
{
    // ...

    /**
     * @Flow\Inject
     * @var EventStoreFactory
     */
    protected $eventStoreFactory;

    // ...

    public function whenChannelWasNotified(ChannelWasNotified $event): void
    {
        $eventStore = $this->eventStoreFactory->create('Acme.Notifications:EventStore');
        $userIds = $this->usersThatSubscribedToChannel($event->getChannelId());
        foreach ($userIds as $userId) {
            $userEvent = new UserWasNotified($userId, $event->getNotificationId(), $event->getMessage(), $event->getTimestamp());
            $streamName = StreamName::fromString('user-' . $userId);
            $eventStore->commit($streamName, DomainEvents::withSingleEvent($userEvent));
        }
    }

    // ...
}
```

<details><summary>:information_source:&nbsp; <b>Note...</b></summary>

> We duplicate some code here to keep it simple. In a productive application you probably want to introduce a central authority to commit events of a certain type
> such as a "NotificationService" or "NotificationCommandHandler"
</details>

Let's test this:

1. Create a new channel
    ```
    ./flow channel:add channel1 "First channel"
    ```

    Output:

    ```
    Channel "First channel" was created with id "channel1"
    ```

2. Send a notification to that channel

    ```
    ./flow channel:notify channel1 "First message to channel1"
    ```

    Output:
    
    ```
    Sent notification <notification-id> to channel channel1
    ```

3. Subscribe a user to the channel:
    
    ```
    ./flow channel:subscribe channel1 user1
    ```

    Output:
    
    ```
    ./flow channel:notify channel1 "Second message to channel1"
    ```

Now, if you check the users Inbox:

```
./flow user:inbox user1
```

..you should get:

```
1 pending message(s) for user user1:
* Second message to channel1 (id: <notification-id>)
```

<details><summary>:information_source:&nbsp; <b>Note...</b></summary>

> The "First message to channel1" did _not_ end up in the users inbox because we subscribed the user to the channel _afterwards_.
</details>

This already seems to work nicely. But with the current implementation it would be hard to tell whether a notification was sent to a specific User directly or via a Channel.

### Event Correlation

Especially in systems with growing complexity it's a very good idea to [:book: correlate](Glossary.md#event-correlation) events so that it's easier to track back the originating trigger for a Notification. 
We can adjust the `ChannelCommandController` and `ChannelNotificationProcessManager` to set a *causation* and *correlation* identifier to every `*WasNotified` event that is "forwarded" to the user using the `DecoratedEvent`-helper:

*Classes/Command/ChannelCommandController.php:*
```php
// ...

final class ChannelCommandController extends CommandController
{

    use Neos\EventSourcing\Event\DecoratedEvent;

    // ...

    public function notifyCommand(string $channelId, string $message): void
    {
        // ...
        $event = new ChannelWasNotified($channelId, $notificationId, $message, $now);
        // We just generate a new correlation ID. It could also be passed in by the client.
        $correlationId = Algorithms::generateUUID();
        $event = DecoratedEvent::addCorrelationIdentifier($event, $correlationId);
        // ...
    }
}
```

*Classes/ChannelNotificationProcessManager.php:*
```php
// ...
final class ChannelNotificationProcessManager implements EventListenerInterface
{
    use Neos\EventSourcing\Event\DecoratedEvent;
    use Neos\EventSourcing\EventStore\RawEvent;

    // ...

    public function whenChannelWasNotified(ChannelWasNotified $event, RawEvent $rawEvent): void
    {
        // ...
        // take over the correlation ID of the incoming event (if it has one)
        /** @var string|null $correlationId */
        $correlationId = $rawEvent->getMetadata()['correlationIdentifier'] ?? null;
        foreach ($userIds as $userId) {
            $userEvent = new UserWasNotified($userId, $event->getNotificationId(), $event->getMessage(), $event->getTimestamp());
            // set the causation ID to the ID of the originating event (ChannelWasNotified)
            $userEvent = DecoratedEvent::addCausationIdentifier($userEvent, $rawEvent->getIdentifier());
            // set the correlation ID if it is set
            if ($correlationId !== null) {
                $userEvent = DecoratedEvent::addCorrelationIdentifier($userEvent, $correlationId);
            }
            // ...
        }
    }
```

<details><summary>:information_source:&nbsp; <b>Note...</b></summary>

> Since the correlation ID is not part of the Domain Event payload, we'll use the RawEvent to get hold of the metadata.
> The RawEvent also contains the Event's `identifier`, `type`, it's raw `payload`, `streamName`, `version`, `sequenceNumber` and the `recordedAt` timestamp
</details>

When you now send a notification to a channel
* The `ChannelWasNotified`  and all resulting `UserWasNotified` events will have the same *correlation id*
* All `UserWasNotified` events will have a *causation id* that is equal to the id of the `ChannelWasNotified` that caused the user notification

### Consistence/Integrity part two

So far there are no consistency checks in place for the Channel management.
One could re-use an existing id when adding a Channel and/or notify a non-existing channel.

To prevent that, we can make use of the `expectedVersion` argument of the `EventStore::commit()` method (see [:book: Concurrency](Glossary.md#concurrency)):

*Classes/Command/ChannelCommandController.php:*
```php
// ...
use Neos\EventSourcing\EventStore\ExpectedVersion;

final class ChannelCommandController extends CommandController
{
    use Neos\EventSourcing\EventStore\Exception\ConcurrencyException;
    use Neos\EventSourcing\EventStore\ExpectedVersion;

    // ...

    public function addCommand(string $channelId, string $label): void
    {
        // ...
        try {
            $eventStore->commit($streamName, DomainEvents::withSingleEvent($event), ExpectedVersion::NO_STREAM);
        } catch (ConcurrencyException $exception) {
            $this->outputLine('<error>A channel with id "%s" already exists</error>', [$channelId]);
            $this->quit(1);
            return;
        }
        // ...
    }

    public function subscribeCommand(string $channelId, string $userId): void
    {
        // ...
        try {
            $eventStore->commit($streamName, DomainEvents::withSingleEvent($event), ExpectedVersion::STREAM_EXISTS);
        } catch (ConcurrencyException $exception) {
            $this->outputLine('<error>A channel with id "%s" does not exist</error>', [$channelId]);
            $this->quit(1);
            return;
        }
        // ...
    }

    public function notifyCommand(string $channelId, string $message): void
    {
        // ...
        try {
            $eventStore->commit($streamName, DomainEvents::withSingleEvent($event), ExpectedVersion::STREAM_EXISTS);
        } catch (ConcurrencyException $exception) {
            $this->outputLine('<error>A channel with id "%s" does not exist</error>', [$channelId]);
            $this->quit(1);
            return;
        }
        // ...
    }
}
```

This is a good first measure, but it won't work for cases where it's possible to _archive_ or _delete_ Channels because the corresponding event stream would still exist afterwards (remember: we never delete events).
So in addition to the `expectedVersion` constraints we could introduce another Read Model that can be queried from the CommandController - like we did with the `Inbox` model.
Since the next milestone has some more advanced requirements, we'll solve that issue otherwise in this case.

## Milestone 3

**NOTE:** This part of the tutorial is still *work in progress*, but here is already a sneak preview:

> * Flood protection: Don't allow more than one Notification to be sent to a Channel within *10 seconds*
> * If a User doesn't acknowledge a Notification within *1 hour* they receive a **Reminder** email
> * Reminders are aggregated, so that no more than one mail per *10 minutes* is sent to one user

Admittedly the first requirement is a little far-fetched. But it's a good example for an invariant that requires [:book: Immediate Consistency](Glossary.md#immediate-consistency).
Using a Read Model to enforce a [:book: Soft Constraint](Glossary.md#soft-constraint) would not work in this case. An evil (or buggy) agent could trigger thousands of notifications before the Read Model is even up to date.

One option would be to carry the Event Stream version to the Read Model und use that as `expectedVersion` (see previous section). That would allow new Channel events only once the Read Model is up-to-date.
In systems with a low event throughput this "pessimistic locking" approach could be feasible. But the more events are handled the more likely you have an outdated Read Model. 

### Event Sourced Aggregate

*Classes/Channel.php*
```php
<?php
declare(strict_types=1);
namespace Acme\Notifications;

use Acme\Notifications\Events\ChannelWasAdded;
use Acme\Notifications\Events\ChannelWasNotified;
use Acme\Notifications\Events\UserWasSubscribedToChannel;
use Neos\EventSourcing\AbstractEventSourcedAggregateRoot;
use Neos\Flow\Utility\Algorithms;

final class Channel extends AbstractEventSourcedAggregateRoot
{
    /**
     * @var string
     */
    private $id;

    public static function create(string $id, string $label): self
    {
        $instance = new static();
        $instance->recordThat(new ChannelWasAdded($id, $label));
        return $instance;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function subscribeUser(string $userId): void
    {
        $this->recordThat(new UserWasSubscribedToChannel($userId, $this->id));
    }

    public function whenChannelWasAdded(ChannelWasAdded $event): void
    {
        $this->id = $event->getChannelId();
    }

    public function notify(string $message): void
    {
        $notificationId = Algorithms::generateUUID();
        $now = new \DateTimeImmutable();
        $this->recordThat(new ChannelWasNotified($this->id, $notificationId, $message, $now));
    }
}
```


```php
// ...

final class Channel extends AbstractEventSourcedAggregateRoot
{
    /**
     * Number of seconds that must pass between a new notification can be sent to the channel
     *
     * @const int
     */
    private const MIN_DELAY_BETWEEN_NOTIFICATIONS = 10;

    /**
     * This local state stores the last time a notification has been sent to this channel
     *
     * @var \DateTimeImmutable|null
     */
    private $lastNotificationTime;

    // ...

    public function notify(string $message): void
    {
        $notificationId = Algorithms::generateUUID();
        $now = new \DateTimeImmutable();
        if ($this->lastNotificationTime !== null
            && $now->getTimestamp() - $this->lastNotificationTime->getTimestamp() < self::MIN_DELAY_BETWEEN_NOTIFICATIONS)
        {
            throw new \RuntimeException(sprintf('You must wait %d seconds before a new notification can be sent to this channel', self::MIN_DELAY_BETWEEN_NOTIFICATIONS), 1570461365);
        }
        $this->recordThat(new ChannelWasNotified($this->id, $notificationId, $message, $now));
    }

    // ...

    public function whenChannelWasNotified(ChannelWasNotified $event): void
    {
        $this->lastNotificationTime = $event->getTimestamp();
    }
}
```

```php
// ...

final class Channel extends AbstractEventSourcedAggregateRoot
{

    // ...

    public function notify(string $message): void
    {
        if ($this->id === null) {
            throw new \RuntimeException('This channel hasn\'t been setup yet', 1592469220);
        }
        // ...
    }
}
```

*Classes/ChannelRepository.php*
```php
<?php
declare(strict_types=1);
namespace Acme\Notifications;

use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class ChannelRepository
{
    /**
     * @Flow\Inject
     * @var EventStoreFactory
     */
    protected $eventStoreFactory;

    public function load(string $channelId): Channel
    {
        $eventStore = $this->eventStoreFactory->create('Acme.Notifications:EventStore');
        $streamName = StreamName::fromString('channel-' . $channelId);
        return Channel::reconstituteFromEventStream($eventStore->load($streamName));
    }

    public function save(Channel $channel): void
    {
        $eventStore = $this->eventStoreFactory->create('Acme.Notifications:EventStore');
        $streamName = StreamName::fromString('channel-' . $channel->getId());
        $eventStore->commit($streamName, $channel->pullUncommittedEvents(), $channel->getReconstitutionVersion());
    }
}
```
