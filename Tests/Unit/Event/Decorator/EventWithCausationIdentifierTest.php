<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\Event\Decorator;

use Neos\EventSourcing\Event\Decorator\EventWithCausationIdentifier;
use Neos\EventSourcing\Event\Decorator\DomainEventWithMetadataInterface;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Tests\UnitTestCase;

class EventWithCausationIdentifierTest extends UnitTestCase
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
     * @expectedException \InvalidArgumentException
     */
    public function constructorDoesntAcceptEmptyCausationId(): void
    {
        new EventWithCausationIdentifier($this->mockEvent, '');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function constructorDoesntAcceptCausationIdExceedingMaxLength(): void
    {
        new EventWithCausationIdentifier($this->mockEvent, str_repeat('x', 256));
    }

    /**
     * @test
     */
    public function originalEventCanBeRetrieved(): void
    {
        $eventWithMetadata = new EventWithCausationIdentifier($this->mockEvent, 'some-id');
        $this->assertSame($this->mockEvent, $eventWithMetadata->getEvent());
    }

    /**
     * @test
     */
    public function causationIdentifierCanBeRetrieved(): void
    {
        $someCausationId = 'some-id';
        $eventWithMetadata = new EventWithCausationIdentifier($this->mockEvent, $someCausationId);
        $metadata = $eventWithMetadata->getMetadata();
        $this->assertSame($someCausationId, $metadata['causationIdentifier']);
    }

    /**
     * @test
     */
    public function metadataIsMergedWhenNestingEventsWithMetadata(): void
    {
        $someMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'bars'], 'causationIdentifier' => 'existing-causation-id', 'correlationIdentifier' => 'existing-causation-id'];
        /** @var DomainEventWithMetadataInterface|\PHPUnit_Framework_MockObject_MockObject $eventWithMetadata */
        $eventWithMetadata = $this->getMockBuilder(DomainEventWithMetadataInterface::class)->getMock();
        $eventWithMetadata->method('getMetadata')->willReturn($someMetadata);

        $nestedEventWithMetadata = new EventWithCausationIdentifier($eventWithMetadata, 'overridden-causation-id');

        $mergedMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'bars'], 'causationIdentifier' => 'overridden-causation-id', 'correlationIdentifier' => 'existing-causation-id'];

        $this->assertSame($mergedMetadata, $nestedEventWithMetadata->getMetadata());
    }

    /**
     * @test
     */
    public function eventIsUnwrappedWhenNestingEventsWithMetadata(): void
    {
        $eventWithMetadata = new EventWithCausationIdentifier($this->mockEvent, 'some-id');
        $nestedEventWithMetadata = new EventWithCausationIdentifier($eventWithMetadata, 'some-id');

        $this->assertSame($this->mockEvent, $nestedEventWithMetadata->getEvent());
    }
}
