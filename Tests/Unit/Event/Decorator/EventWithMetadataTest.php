<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\Event\Decorator;

use Neos\EventSourcing\Event\Decorator\EventWithMetadata;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Tests\UnitTestCase;

class EventWithMetadataTest extends UnitTestCase
{
    /**
     * @var DomainEventInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockEvent;

    public function setUp()
    {
        $this->mockEvent = $this->getMockBuilder(DomainEventInterface::class)->getMock();
    }

    /**
     * @test
     */
    public function originalEventCanBeRetrieved(): void
    {
        $eventWithMetadata = new EventWithMetadata($this->mockEvent, []);
        $this->assertSame($this->mockEvent, $eventWithMetadata->getEvent());
    }

    /**
     * @test
     */
    public function metadataCanBeRetrieved(): void
    {
        $someMetadata = ['foo' => ['bar' => 'Baz']];
        $eventWithMetadata = new EventWithMetadata($this->mockEvent, $someMetadata);
        $this->assertSame($someMetadata, $eventWithMetadata->getMetadata());
    }

    /**
     * @test
     */
    public function metadataIsMergedWhenNestingEventsWithMetadata(): void
    {
        $someMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'bars']];
        $eventWithMetadata = new EventWithMetadata($this->mockEvent, $someMetadata);

        $someMoreMetadata = ['foo' => ['foos' => 'overridden'], 'another' => 'entry'];
        $nestedEventWithMetadata = new EventWithMetadata($eventWithMetadata, $someMoreMetadata);

        $mergedMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'overridden'], 'another' => 'entry'];

        $this->assertSame($mergedMetadata, $nestedEventWithMetadata->getMetadata());
    }

    /**
     * @test
     */
    public function eventIsUnwrappedWhenNestingEventsWithMetadata(): void
    {
        $eventWithMetadata = new EventWithMetadata($this->mockEvent, []);
        $nestedEventWithMetadata = new EventWithMetadata($eventWithMetadata, []);

        $this->assertSame($this->mockEvent, $nestedEventWithMetadata->getEvent());
    }
}
