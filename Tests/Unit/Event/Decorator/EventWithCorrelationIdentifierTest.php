<?php
namespace Neos\EventSourcing\Tests\Unit\Event\Decorator;

use Neos\EventSourcing\Event\Decorator\EventWithCorrelationIdentifier;
use Neos\EventSourcing\Event\Decorator\EventWithMetadataInterface;
use Neos\EventSourcing\Event\EventInterface;
use Neos\Flow\Tests\UnitTestCase;

class EventWithCorrelationIdentifierTest extends UnitTestCase
{
    /**
     * @var EventInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockEvent;

    public function setUp()
    {
        $this->mockEvent = $this->getMockBuilder(EventInterface::class)->getMock();
    }

    /**
     * @test
     * @expectedException \Neos\EventSourcing\Exception
     */
    public function constructorDoesntAcceptEmptyCorrelationId()
    {
        new EventWithCorrelationIdentifier($this->mockEvent, '');
    }

    /**
     * @test
     * @expectedException \Neos\EventSourcing\Exception
     */
    public function constructorDoesntAcceptCorrelationIdExceedingMaxLength()
    {
        new EventWithCorrelationIdentifier($this->mockEvent, str_repeat('x', 256));
    }

    /**
     * @test
     */
    public function originalEventCanBeRetrieved()
    {
        $eventWithMetadata = new EventWithCorrelationIdentifier($this->mockEvent, 'some-id');
        $this->assertSame($this->mockEvent, $eventWithMetadata->getEvent());
    }

    /**
     * @test
     */
    public function correlationIdentifierCanBeRetrieved()
    {
        $someCorrelationId = 'some-id';
        $eventWithMetadata = new EventWithCorrelationIdentifier($this->mockEvent, $someCorrelationId);
        $metadata = $eventWithMetadata->getMetadata();
        $this->assertSame($someCorrelationId, $metadata['correlationIdentifier']);
    }

    /**
     * @test
     */
    public function metadataIsMergedWhenNestingEventsWithMetadata()
    {
        $someMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'bars'], 'causationIdentifier' => 'existing-causation-id', 'correlationIdentifier' => 'existing-correlation-id'];
        /** @var EventWithMetadataInterface|\PHPUnit_Framework_MockObject_MockObject $eventWithMetadata */
        $eventWithMetadata = $this->getMockBuilder(EventWithMetadataInterface::class)->getMock();
        $eventWithMetadata->expects($this->any())->method('getMetadata')->will($this->returnValue($someMetadata));

        $nestedEventWithMetadata = new EventWithCorrelationIdentifier($eventWithMetadata, 'overridden-correlation-id');

        $mergedMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'bars'], 'causationIdentifier' => 'existing-causation-id', 'correlationIdentifier' => 'overridden-correlation-id'];

        $this->assertSame($mergedMetadata, $nestedEventWithMetadata->getMetadata());
    }

    /**
     * @test
     */
    public function eventIsUnwrappedWhenNestingEventsWithMetadata()
    {
        $eventWithMetadata = new EventWithCorrelationIdentifier($this->mockEvent, 'some-id');
        $nestedEventWithMetadata = new EventWithCorrelationIdentifier($eventWithMetadata, 'some-id');

        $this->assertSame($this->mockEvent, $nestedEventWithMetadata->getEvent());
    }
}