<?php
namespace Neos\Cqrs\Tests\Unit\Event;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Neos\Cqrs\Event\EventBus;
use Neos\Cqrs\Event\EventBusInterface;
use Neos\Cqrs\Event\EventInterface;
use Neos\Cqrs\Event\EventTransport;
use Neos\Cqrs\Event\Exception\EventBusException;
use Neos\Cqrs\EventListener\EventListenerContainer;
use Neos\Cqrs\EventListener\EventListenerLocatorInterface;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * CommandBusTest
 */
class EventBusTest extends UnitTestCase
{
    /**
     * @var EventBusInterface
     */
    protected $eventBus;

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var EventListenerLocatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockLocator;

    /**
     * @var SystemLoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockLogger;

    /**
     * @var EventTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockTransport;

    /**
     * @var EventInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockEvent;

    public function setUp()
    {
        $this->eventBus = new EventBus();

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);

        $this->mockLocator = $this->createMock(EventListenerLocatorInterface::class);
        $this->inject($this->eventBus, 'locator', $this->mockLocator);

        $this->mockLogger = $this->createMock(SystemLoggerInterface::class);
        $this->inject($this->eventBus, 'logger', $this->mockLogger);

        $this->mockTransport = $this->createMock(EventTransport::class);

        $this->mockEvent = $this->getMockBuilder(EventInterface::class)
            ->setMockClassName('ActionCreated')
            ->getMock();

        $this->mockTransport
            ->expects($this->once())
            ->method('getEvent')
            ->willReturn($this->mockEvent);
    }

    /**
     * @test
     */
    public function handleSkipSilentlyEventWithoutListeners()
    {
        $this->mockLocator
            ->expects($this->once())
            ->method('getListeners')
            ->with($this->mockEvent)
            ->willReturn($this->generator([]));

        $this->eventBus->handle($this->mockTransport);
    }

    /**
     * @test
     */
    public function handleIteratareOverListeners()
    {
        $mockContainer = $this->getMockBuilder(EventListenerContainer::class)
            ->setConstructorArgs([['Your\Package\EventListener', 'onActionCreated'], $this->mockObjectManager])
            ->getMock();

        $mockContainer
            ->expects($this->once())
            ->method('when');

        $this->mockLocator
            ->expects($this->once())
            ->method('getListeners')
            ->with($this->mockEvent)
            ->willReturn($this->generator([$mockContainer]));

        $this->eventBus->handle($this->mockTransport);
    }

    /**
     * @test
     * @expectedException \Neos\Cqrs\Event\Exception\EventBusException
     */
    public function handlerLogException()
    {
        $exception = new EventBusException();

        $this->mockLogger
            ->expects($this->once())
            ->method('logException')
            ->with($exception);

        $mockContainer = $this->getMockBuilder(EventListenerContainer::class)
            ->setConstructorArgs([['Your\Package\EventListener', 'onActionCreated'], $this->mockObjectManager])
            ->getMock();

        $mockContainer->expects($this->once())
            ->method('when')
            ->willThrowException($exception);

        $this->mockLocator->expects($this->once())
            ->method('getListeners')
            ->with($this->mockEvent)
            ->willReturn($this->generator([$mockContainer]));

        $this->eventBus->handle($this->mockTransport);
    }

    /**
     * @param array $array
     * @return \Generator
     */
    protected function generator(array $array)
    {
        foreach ($array as $value) {
            yield $value;
        }
    }
}
