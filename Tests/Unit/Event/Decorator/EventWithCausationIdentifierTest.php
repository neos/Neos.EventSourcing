<?php
namespace Neos\EventSourcing\Tests\Unit\Event\Decorator;

use Neos\EventSourcing\Event\Decorator\EventWithCausationIdentifier;
use Neos\EventSourcing\Event\Decorator\EventWithMetadataInterface;
use Neos\EventSourcing\Event\EventInterface;
use Neos\Flow\Tests\UnitTestCase;

class EventWithCausationIdentifierTest extends UnitTestCase
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
    public function constructorDoesntAcceptEmptyCausationId()
    {
        new EventWithCausationIdentifier($this->mockEvent, '');
    }

    /**
     * @test
     * @expectedException \Neos\EventSourcing\Exception
     */
    public function constructorDoesntAcceptCausationIdExceedingMaxLength()
    {
        new EventWithCausationIdentifier($this->mockEvent, str_repeat('x', 256));
    }

    /**
     * @test
     */
    public function originalEventCanBeRetrieved()
    {
        $eventWithMetadata = new EventWithCausationIdentifier($this->mockEvent, 'some-id');
        $this->assertSame($this->mockEvent, $eventWithMetadata->getEvent());
    }

    /**
     * @test
     */
    public function causationIdentifierCanBeRetrieved()
    {
        $someCausationId = 'some-id';
        $eventWithMetadata = new EventWithCausationIdentifier($this->mockEvent, $someCausationId);
        $metadata = $eventWithMetadata->getMetadata();
        $this->assertSame($someCausationId, $metadata['causationIdentifier']);
    }

    /**
     * @test
     */
    public function metadataIsMergedWhenNestingEventsWithMetadata()
    {
        $someMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'bars'], 'causationIdentifier' => 'existing-causation-id', 'correlationIdentifier' => 'existing-causation-id'];
        /** @var EventWithMetadataInterface|\PHPUnit_Framework_MockObject_MockObject $eventWithMetadata */
        $eventWithMetadata = $this->getMockBuilder(EventWithMetadataInterface::class)->getMock();
        $eventWithMetadata->expects($this->any())->method('getMetadata')->will($this->returnValue($someMetadata));

        $nestedEventWithMetadata = new EventWithCausationIdentifier($eventWithMetadata, 'overridden-causation-id');

        $mergedMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'bars'], 'causationIdentifier' => 'overridden-causation-id', 'correlationIdentifier' => 'existing-causation-id'];

        $this->assertSame($mergedMetadata, $nestedEventWithMetadata->getMetadata());
    }

    /**
     * @test
     */
    public function eventIsUnwrappedWhenNestingEventsWithMetadata()
    {
        $eventWithMetadata = new EventWithCausationIdentifier($this->mockEvent, 'some-id');
        $nestedEventWithMetadata = new EventWithCausationIdentifier($eventWithMetadata, 'some-id');

        $this->assertSame($this->mockEvent, $nestedEventWithMetadata->getEvent());
    }
}