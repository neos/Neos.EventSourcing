<?php
namespace Neos\EventSourcing\Tests\Unit\EventStore;

use Neos\EventSourcing\Event\EventInterface;
use Neos\EventSourcing\Event\EventWithMetadata;
use Neos\Flow\Tests\UnitTestCase;

class EventWithMetadataTest extends UnitTestCase
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
     */
    public function originalEventCanBeRetrieved()
    {
        $eventWithMetadata = new EventWithMetadata($this->mockEvent, []);
        $this->assertSame($this->mockEvent, $eventWithMetadata->getEvent());
    }

    /**
     * @test
     */
    public function metadataCanBeRetrieved()
    {
        $someMetadata = ['foo' => ['bar' => 'Baz']];
        $eventWithMetadata = new EventWithMetadata($this->mockEvent, $someMetadata);
        $this->assertSame($someMetadata, $eventWithMetadata->getMetadata());
    }


    /**
     * @test
     */
    public function eventsWithMetadataCanBeNested()
    {
        $someMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'bars']];
        $eventWithMetadata = new EventWithMetadata($this->mockEvent, $someMetadata);

        $someMoreMetadata = ['foo' => ['foos' => 'overridden'], 'another' => 'entry'];
        $nestedEventWithMetadata = new EventWithMetadata($eventWithMetadata, $someMoreMetadata);

        $mergedMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'overridden'], 'another' => 'entry'];

        $this->assertSame($mergedMetadata, $nestedEventWithMetadata->getMetadata());
    }
}