<?php
namespace Neos\Cqrs\Tests\Unit\EventListener;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Neos\Cqrs\Event\EventInterface;
use Neos\Cqrs\Event\EventTransport;
use Neos\Cqrs\EventListener\EventListenerContainer;
use Neos\Cqrs\EventListener\EventListenerInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * CommandBusTest
 */
class EventListenerContainerTest extends UnitTestCase
{
    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var EventListenerContainer
     */
    protected $eventListenerContainer;

    public function setUp()
    {
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $listener = ['Your\Package\EventListener', 'onActionCreated'];
        $this->eventListenerContainer = new EventListenerContainer($listener, $this->mockObjectManager);
        $this->inject($this->eventListenerContainer, 'objectManager', $this->mockObjectManager);
    }

    /**
     * @test
     */
    public function getListenerClass()
    {
        $this->assertSame('Your\Package\EventListener', $this->eventListenerContainer->getListenerClass());
    }

    /**
     * @test
     */
    public function getListenerMethod()
    {
        $this->assertSame('onActionCreated', $this->eventListenerContainer->getListenerMethod());
    }

    /**
     * @test
     */
    public function whenCallTheEventListener()
    {
        $mockEvent = $this->createMock(EventInterface::class);

        $mockEventTransport = $this->createMock(EventTransport::class);
        $mockEventTransport
            ->expects($this->once())
            ->method('getEvent')
            ->willReturn($mockEvent);

        $mockEventListener = $this->getMockBuilder(EventListenerInterface::class)
            ->setMethods(['onActionCreated'])
            ->getMock();

        $mockEventListener
            ->expects($this->once())
            ->method('onActionCreated')
            ->with($mockEvent);

        $this->mockObjectManager
            ->expects($this->once())
            ->method('get')
            ->with('Your\Package\EventListener')
            ->willReturn($mockEventListener);

        $this->eventListenerContainer->when($mockEventTransport);
    }
}
