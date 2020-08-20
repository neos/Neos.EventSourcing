<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\EventListener;

use DG\BypassFinals;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Neos\EventSourcing\EventListener\AppliedEventsStorage\AppliedEventsStorageInterface;
use Neos\EventSourcing\EventListener\EventListenerInterface;
use Neos\EventSourcing\EventListener\EventListenerInvoker;
use Neos\EventSourcing\EventListener\Exception\EventCouldNotBeAppliedException;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\EventStream;
use Neos\EventSourcing\EventStore\EventStreamIteratorInterface;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\EventSourcing\EventStore\Storage\InMemory\InMemoryStreamIterator;
use Neos\EventSourcing\EventStore\StreamAwareEventListenerInterface;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\EventSourcing\Tests\Unit\EventListener\Fixture\AppliedEventsStorageEventListener;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;

class EventListenerInvokerTest extends UnitTestCase
{
    /**
     * @var EventListenerInvoker
     */
    private $eventListenerInvoker;

    /**
     * @var EventStore|MockObject
     */
    private $mockEventStore;

    /**
     * @var Connection|MockObject
     */
    private $mockConnection;

    /**
     * @var EventStream|MockObject
     */
    private $mockEventStream;

    /**
     * @var EventListenerInterface|MockObject
     */
    private $mockEventListener;

    /**
     * @var EventNormalizer|MockObject
     */
    private $mockEventNormalizer;

    /**
     * @var AppliedEventsStorageInterface|MockObject
     */
    private $mockAppliedEventsStorage;

    /** @noinspection ClassMockingCorrectnessInspection */
    public function setUp(): void
    {
        BypassFinals::enable();
        $this->mockEventStore = $this->getMockBuilder(EventStore::class)->disableOriginalConstructor()->getMock();

        $this->mockConnection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $this->mockConnection->method('getTransactionNestingLevel')->willReturn(0);
        $mockPlatform = $this->getMockBuilder(AbstractPlatform::class)->getMock();
        $this->mockConnection->method('getDatabasePlatform')->willReturn($mockPlatform);

        $this->mockAppliedEventsStorage = $this->getMockBuilder(AppliedEventsStorageInterface::class)->getMock();

        $this->mockEventListener = $this->getMockBuilder(AppliedEventsStorageEventListener::class)->disableOriginalConstructor()->getMock();
        $this->mockEventListener->method('getAppliedEventsStorage')->willReturn($this->mockAppliedEventsStorage);

        $this->eventListenerInvoker = new EventListenerInvoker($this->mockEventStore, $this->mockEventListener, $this->mockConnection);

        $this->mockEventStream = $this->getMockBuilder(EventStream::class)->disableOriginalConstructor()->getMock();
        $this->mockEventNormalizer = $this->getMockBuilder(EventNormalizer::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     * @throws
     */
    public function catchUpAppliesEventsUpToTheDefinedMaximumSequenceNumber(): void
    {
        $eventRecords = [];
        for ($sequenceNumber = 1; $sequenceNumber < 123; $sequenceNumber++) {
            $eventRecords[] = [
                'sequencenumber' => $sequenceNumber,
                'type' => 'FooEventType',
                'payload' => json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR, 512),
                'metadata' => json_encode([], JSON_THROW_ON_ERROR, 512),
                'recordedat' => '2020-08-17',
                'stream' => 'FooStreamName',
                'version' => $sequenceNumber,
                'id' => Uuid::uuid4()->toString()
            ];
        }

        $streamIterator = new InMemoryStreamIterator();
        $streamIterator->setEventRecords($eventRecords);
        $eventStream = new EventStream(StreamName::fromString('FooStreamName'), $streamIterator, $this->mockEventNormalizer);

        // Simulate that the first 10 events have already been applied:
        $this->mockAppliedEventsStorage->expects($this->atLeastOnce())->method('reserveHighestAppliedEventSequenceNumber')->willReturn(10);
        $this->mockEventStore->expects($this->once())->method('load')->with(StreamName::all(), 11)->willReturn($eventStream);

        $this->eventListenerInvoker = new EventListenerInvoker($this->mockEventStore, $this->mockEventListener, $this->mockConnection);

        $appliedEventsCounter = 0;
        $this->eventListenerInvoker->onProgress(static function() use(&$appliedEventsCounter){
            $appliedEventsCounter ++;
        });

        $this->eventListenerInvoker = $this->eventListenerInvoker->withMaximumSequenceNumber(50);
        $this->eventListenerInvoker->catchUp();

        $this->assertSame(40, $appliedEventsCounter);
    }

    /**
     * @test
     * @throws EventCouldNotBeAppliedException
     */
    public function catchUpPassesRespectsReservedSequenceNumber(): void
    {
        $this->mockAppliedEventsStorage->expects($this->atLeastOnce())->method('reserveHighestAppliedEventSequenceNumber')->willReturn(123);
        $this->mockEventStore->expects($this->once())->method('load')->with(StreamName::all(), 124)->willReturn($this->mockEventStream);
        $this->eventListenerInvoker->catchUp();
    }

    /**
     * @test
     * @throws EventCouldNotBeAppliedException
     */
    public function catchUpPassesRespectsStreamAwareEventListenerInterface(): void
    {
        $streamName = StreamName::fromString('some-stream');
        $mockEventListener = $this->buildMockEventListener($streamName);
        $this->eventListenerInvoker = new EventListenerInvoker($this->mockEventStore, $mockEventListener, $this->mockConnection);
        $this->mockEventStore->expects($this->once())->method('load')->with($streamName, 1)->willReturn($this->mockEventStream);
        $this->eventListenerInvoker->catchUp();
    }

    /**
     * @param StreamName|null $streamName
     * @return EventListenerInterface
     */
    private function buildMockEventListener(StreamName $streamName = null): EventListenerInterface
    {
        $listenerClassName = 'Mock_EventListener_' . md5(uniqid('', true));
        $listenerCode = 'class ' . $listenerClassName . ' implements ' . EventListenerInterface::class;
        if ($streamName !== null) {
            $listenerCode .= ', ' . StreamAwareEventListenerInterface::class;
        }
        $listenerCode .= ' {';
        if ($streamName !== null) {
            $listenerCode .= 'public static function listensToStream(): ' . StreamName::class . ' { return ' . StreamName::class . '::fromString(\'' . $streamName . '\'); }';
        }
        $listenerCode .= '}';

        eval($listenerCode);
        return new $listenerClassName();
    }

}
