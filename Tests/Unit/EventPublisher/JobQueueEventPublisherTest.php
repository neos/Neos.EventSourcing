<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\EventPublisher;

use Flowpack\JobQueue\Common\Job\JobManager;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventListener\Mapping\EventToListenerMapping;
use Neos\EventSourcing\EventListener\Mapping\EventToListenerMappings;
use Neos\EventSourcing\EventPublisher\JobQueue\CatchUpEventListenerJob;
use Neos\EventSourcing\EventPublisher\JobQueueEventPublisher;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class JobQueueEventPublisherTest extends UnitTestCase
{

    /**
     * @var JobManager|MockObject
     */
    private $mockJobManager;

    /**
     * @var DomainEventInterface|MockObject
     */
    private $mockEvent1;

    /**
     * @var DomainEventInterface|MockObject
     */
    private $mockEvent2;

    /**
     * @var DomainEvents|MockObject
     */
    private $mockEvents;

    public function setUp(): void
    {
        $this->mockJobManager = $this->getMockBuilder(JobManager::class)->disableOriginalConstructor()->getMock();

        $this->mockEvent1 = $this->getMockBuilder(DomainEventInterface::class)->getMock();
        $this->mockEvent2 = $this->getMockBuilder(DomainEventInterface::class)->getMock();
        $this->mockEvents = DomainEvents::fromArray([$this->mockEvent1, $this->mockEvent2]);
    }

    /**
     * @test
     */
    public function publishDoesNotQueueJobsIfDomainEventsAreEmpty(): void
    {
        $jobQueueEventPublisher = $this->buildPublisher('some-event-store', EventToListenerMappings::create());

        $this->mockJobManager->expects($this->never())->method('queue');

        $jobQueueEventPublisher->publish(DomainEvents::createEmpty());
    }

    /**
     * @test
     */
    public function publishDoesNotQueueJobsIfMappingsIsEmpty(): void
    {
        $jobQueueEventPublisher = $this->buildPublisher('some-event-store', EventToListenerMappings::create());

        $this->mockJobManager->expects($this->never())->method('queue');

        $mockEvent1 = $this->getMockBuilder(DomainEventInterface::class)->getMock();
        $mockEvent2 = $this->getMockBuilder(DomainEventInterface::class)->getMock();
        $jobQueueEventPublisher->publish(DomainEvents::fromArray([$mockEvent1, $mockEvent2]));
    }

    /**
     * @test
     */
    public function publishDoesNotQueueJobsIfNoMatchingMappingExists(): void
    {
        $mappings = EventToListenerMappings::create()
            ->withMapping(EventToListenerMapping::create('SomeEventClassName', 'SomeListenerClassName', []))
            ->withMapping(EventToListenerMapping::create('SomeOtherEventClassName', 'SomeListenerClassName', []));
        $jobQueueEventPublisher = $this->buildPublisher('some-event-store', $mappings);

        $this->mockJobManager->expects($this->never())->method('queue');

        $mockEvent1 = $this->getMockBuilder(DomainEventInterface::class)->getMock();
        $mockEvent2 = $this->getMockBuilder(DomainEventInterface::class)->getMock();
        $jobQueueEventPublisher->publish(DomainEvents::fromArray([$mockEvent1, $mockEvent2]));
    }

    /**
     * @test
     */
    public function publishPassesTheEventStoreIdToTheJob(): void
    {
        $someEventStoreId = 'some-event-store';
        $mappings = EventToListenerMappings::create()
            ->withMapping(EventToListenerMapping::create(get_class($this->mockEvent1), 'SomeListenerClassName', []));
        $jobQueueEventPublisher = $this->buildPublisher($someEventStoreId, $mappings);

        $this->mockJobManager->method('queue')->willReturnCallback(static function(string $_, CatchUpEventListenerJob $job) use ($someEventStoreId) {
            self::assertSame($someEventStoreId, $job->getEventStoreIdentifier());
        });

        $jobQueueEventPublisher->publish($this->mockEvents);
    }

    /**
     * @test
     */
    public function publishQueuesTheJobInTheDefaultQueueByDefault(): void
    {
        $mappings = EventToListenerMappings::create()
            ->withMapping(EventToListenerMapping::create(get_class($this->mockEvent1), 'SomeListenerClassName', []));
        $jobQueueEventPublisher = $this->buildPublisher('event-store-id', $mappings);

        $this->mockJobManager->method('queue')->willReturnCallback(static function($queueName) {
            self::assertSame('neos-eventsourcing', $queueName);
        });

        $jobQueueEventPublisher->publish($this->mockEvents);
    }

    /**
     * @test
     */
    public function publishQueuesTheJobInTheSpecifiedQueue(): void
    {
        $queueName = 'Some-Queue';
        $mappings = EventToListenerMappings::create()
            ->withMapping(EventToListenerMapping::create(get_class($this->mockEvent1), 'SomeListenerClassName', ['queueName' => $queueName]));
        $jobQueueEventPublisher = $this->buildPublisher('event-store-id', $mappings);

        $this->mockJobManager->method('queue')->willReturnCallback(static function(string $actualQueueName) use ($queueName) {
            self::assertSame($queueName, $actualQueueName);
        });

        $jobQueueEventPublisher->publish($this->mockEvents);
    }

    /**
     * @test
     */
    public function publishPassesQueueOptionsToJob(): void
    {
        $queueOptions = ['foo' => ['bar' => 'Baz']];
        $mappings = EventToListenerMappings::create()
            ->withMapping(EventToListenerMapping::create(get_class($this->mockEvent1), 'SomeListenerClassName', ['queueOptions' => $queueOptions]));
        $jobQueueEventPublisher = $this->buildPublisher('event-store-id', $mappings);

        $this->mockJobManager->method('queue')->willReturnCallback(static function(string $_, CatchUpEventListenerJob $__, array $actualOptions) use ($queueOptions) {
            self::assertSame($queueOptions, $actualOptions);
        });

        $jobQueueEventPublisher->publish($this->mockEvents);
    }

    /**
     * @test
     */
    public function publishQueuesOnlyOneJobPerListener(): void
    {
        $eventListenerClassName = 'SomeListenerClassName';
        $mappings = EventToListenerMappings::create()
            ->withMapping(EventToListenerMapping::create(get_class($this->mockEvent1), $eventListenerClassName, []))
            ->withMapping(EventToListenerMapping::create(get_class($this->mockEvent2), $eventListenerClassName, []));
        $jobQueueEventPublisher = $this->buildPublisher('event-store-id', $mappings);

        $this->mockJobManager->expects($this->once())->method('queue')->willReturnCallback(static function(string $_, CatchUpEventListenerJob $job) use ($eventListenerClassName) {
            self::assertSame($eventListenerClassName, $job->getListenerClassName());
        });

        $jobQueueEventPublisher->publish($this->mockEvents);
    }

    /**
     * @test
     */
    public function publishQueuesAJobForEachListener(): void
    {
        $mappings = EventToListenerMappings::create()
            ->withMapping(EventToListenerMapping::create(get_class($this->mockEvent1), 'SomeListenerClassName1', []))
            ->withMapping(EventToListenerMapping::create(get_class($this->mockEvent1), 'SomeListenerClassName2', []))
            ->withMapping(EventToListenerMapping::create(get_class($this->mockEvent2), 'SomeListenerClassName1', []));
        $jobQueueEventPublisher = $this->buildPublisher('event-store-id', $mappings);

        $this->mockJobManager->expects($this->at(0))->method('queue')->willReturnCallback(static function(string $_, CatchUpEventListenerJob $job) {
            self::assertSame('SomeListenerClassName1', $job->getListenerClassName());
        });

        $this->mockJobManager->expects($this->at(1))->method('queue')->willReturnCallback(static function(string $_, CatchUpEventListenerJob $job) {
            self::assertSame('SomeListenerClassName2', $job->getListenerClassName());
        });

        $jobQueueEventPublisher->publish($this->mockEvents);
    }

    private function buildPublisher(string $eventStoreIdentifier, EventToListenerMappings $mappings): JobQueueEventPublisher
    {
        $publisher = new JobQueueEventPublisher($eventStoreIdentifier, $mappings);
        $this->inject($publisher, 'jobManager', $this->mockJobManager);
        return $publisher;
    }
}
