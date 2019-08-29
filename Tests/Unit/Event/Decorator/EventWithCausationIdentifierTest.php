<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\Event\Decorator;

use Neos\EventSourcing\Event\Decorator\EventWithCausationIdentifier;
use Neos\EventSourcing\Event\Decorator\DomainEventWithMetadataInterface;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EventWithCausationIdentifierTest extends UnitTestCase
{
    /**
     * @var DomainEventInterface|MockObject
     */
    private $mockEvent;

    public function setUp(): void
    {
        $this->mockEvent = $this->getMockBuilder(DomainEventInterface::class)->getMock();
    }

    /**
     * @test
     */
    public function constructorDoesntAcceptEmptyCausationId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new EventWithCausationIdentifier($this->mockEvent, '');
    }

    /**
     * @test
     */
    public function constructorDoesntAcceptCausationIdExceedingMaxLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new EventWithCausationIdentifier($this->mockEvent, str_repeat('x', 256));
    }

    /**
     * @test
     */
    public function originalEventCanBeRetrieved(): void
    {
        $eventWithMetadata = new EventWithCausationIdentifier($this->mockEvent, 'some-id');
        self::assertSame($this->mockEvent, $eventWithMetadata->getEvent());
    }

    /**
     * @test
     */
    public function causationIdentifierCanBeRetrieved(): void
    {
        $someCausationId = 'some-id';
        $eventWithMetadata = new EventWithCausationIdentifier($this->mockEvent, $someCausationId);
        $metadata = $eventWithMetadata->getMetadata();
        self::assertSame($someCausationId, $metadata['causationIdentifier']);
    }

    /**
     * @test
     */
    public function metadataIsMergedWhenNestingEventsWithMetadata(): void
    {
        $someMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'bars'], 'causationIdentifier' => 'existing-causation-id', 'correlationIdentifier' => 'existing-causation-id'];
        /** @var DomainEventWithMetadataInterface|MockObject $eventWithMetadata */
        $eventWithMetadata = $this->getMockBuilder(DomainEventWithMetadataInterface::class)->getMock();
        $eventWithMetadata->method('getMetadata')->willReturn($someMetadata);

        $nestedEventWithMetadata = new EventWithCausationIdentifier($eventWithMetadata, 'overridden-causation-id');

        $mergedMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'bars'], 'causationIdentifier' => 'overridden-causation-id', 'correlationIdentifier' => 'existing-causation-id'];

        self::assertSame($mergedMetadata, $nestedEventWithMetadata->getMetadata());
    }

    /**
     * @test
     */
    public function eventIsUnwrappedWhenNestingEventsWithMetadata(): void
    {
        $eventWithMetadata = new EventWithCausationIdentifier($this->mockEvent, 'some-id');
        $nestedEventWithMetadata = new EventWithCausationIdentifier($eventWithMetadata, 'some-id');

        self::assertSame($this->mockEvent, $nestedEventWithMetadata->getEvent());
    }
}
