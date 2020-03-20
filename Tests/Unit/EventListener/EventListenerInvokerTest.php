<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\EventListener;

use DG\BypassFinals;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Neos\EventSourcing\EventListener\AppliedEventsStorage\AppliedEventsStorageInterface;
use Neos\EventSourcing\EventListener\EventListenerInterface;
use Neos\EventSourcing\EventListener\EventListenerInvoker;
use Neos\EventSourcing\EventListener\ProvidesAppliedEventsStorageInterface;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\EventStream;
use Neos\EventSourcing\EventStore\StreamAwareEventListenerInterface;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

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
     * @var EventListenerInterface|ProvidesAppliedEventsStorageInterface|MockObject
     */
    private $mockEventListener;

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

        $this->mockEventListener = $this->getMockBuilder(EventListenerInterface::class)->getMock();

        $this->eventListenerInvoker = new EventListenerInvoker($this->mockEventStore, $this->mockEventListener, $this->mockConnection);

        $this->mockAppliedEventsStorage = $this->getMockBuilder(AppliedEventsStorageInterface::class)->getMock();

        $this->mockEventStream = $this->getMockBuilder(EventStream::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function catchUpPassesRespectsReservedSequenceNumber(): void
    {
        $this->mockConnection->method('fetchColumn')->willReturn(123);
        $this->mockEventStore->expects($this->once())->method('load')->with(StreamName::all(), 124)->willReturn($this->mockEventStream);
        $this->eventListenerInvoker->catchUp();
    }


    /**
     * @test
     */
    public function catchUpPassesRespectsProvidesAppliedEventsStorageInterface(): void
    {
        /** @var EventListenerInterface|MockObject $mockEventListener */
        $mockEventListener = $this->getMockBuilder([EventListenerInterface::class, ProvidesAppliedEventsStorageInterface::class])->getMock();
        $mockEventListener->method('getAppliedEventsStorage')->willReturn($this->mockAppliedEventsStorage);

        $this->eventListenerInvoker = new EventListenerInvoker($this->mockEventStore, $mockEventListener, $this->mockConnection);

        $this->mockAppliedEventsStorage->expects($this->atLeastOnce())->method('reserveHighestAppliedEventSequenceNumber')->willReturn(123);
        $this->mockEventStore->expects($this->once())->method('load')->with(StreamName::all(), 124)->willReturn($this->mockEventStream);
        $this->eventListenerInvoker->catchUp();
    }

    /**
     * @test
     */
    public function catchUpPassesRespectsStreamAwareEventListenerInterface(): void
    {
        $streamName = StreamName::fromString('some-stream');
        $mockEventListener = $this->buildMockEventListener($streamName);
        $this->eventListenerInvoker = new EventListenerInvoker($this->mockEventStore, $mockEventListener, $this->mockConnection);
        $this->mockEventStore->expects($this->once())->method('load')->with($streamName, 1)->willReturn($this->mockEventStream);
        $this->eventListenerInvoker->catchUp();
    }

    private function buildMockEventListener(StreamName $streamName = null): EventListenerInterface
    {
        $listenerClassName = 'Mock_EventListener_' . md5(uniqid('', true));
        $listenerCode = 'class ' . $listenerClassName . ' implements ' . EventListenerInterface::class;
        if ($streamName !== null) {
            $listenerCode .= ', ' . StreamAwareEventListenerInterface::class;
        }
        $listenerCode .= ' {';
        if ($streamName !== null) {
            $listenerCode .= 'public static function listensToStream(): ' . StreamName::class . ' { return ' . StreamName::class . '::fromString(\'' . (string)$streamName . '\'); }';
        }
        $listenerCode .= '}';

        eval($listenerCode);
        return new $listenerClassName();
    }

}
