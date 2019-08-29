<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\Event\Decorator;

use Neos\EventSourcing\Event\Decorator\EventWithCorrelationIdentifier;
use Neos\EventSourcing\Event\Decorator\DomainEventWithMetadataInterface;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EventWithCorrelationIdentifierTest extends UnitTestCase
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
    public function constructorDoesntAcceptEmptyCorrelationId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new EventWithCorrelationIdentifier($this->mockEvent, '');
    }

    /**
     * @test
     */
    public function constructorDoesntAcceptCorrelationIdExceedingMaxLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new EventWithCorrelationIdentifier($this->mockEvent, str_repeat('x', 256));
    }

    /**
     * @test
     */
    public function originalEventCanBeRetrieved(): void
    {
        $eventWithMetadata = new EventWithCorrelationIdentifier($this->mockEvent, 'some-id');
        self::assertSame($this->mockEvent, $eventWithMetadata->getEvent());
    }

    /**
     * @test
     */
    public function correlationIdentifierCanBeRetrieved(): void
    {
        $someCorrelationId = 'some-id';
        $eventWithMetadata = new EventWithCorrelationIdentifier($this->mockEvent, $someCorrelationId);
        $metadata = $eventWithMetadata->getMetadata();
        self::assertSame($someCorrelationId, $metadata['correlationIdentifier']);
    }

    /**
     * @test
     */
    public function metadataIsMergedWhenNestingEventsWithMetadata(): void
    {
        $someMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'bars'], 'causationIdentifier' => 'existing-causation-id', 'correlationIdentifier' => 'existing-correlation-id'];
        /** @var DomainEventWithMetadataInterface|MockObject $eventWithMetadata */
        $eventWithMetadata = $this->getMockBuilder(DomainEventWithMetadataInterface::class)->getMock();
        $eventWithMetadata->method('getMetadata')->willReturn($someMetadata);

        $nestedEventWithMetadata = new EventWithCorrelationIdentifier($eventWithMetadata, 'overridden-correlation-id');

        $mergedMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'bars'], 'causationIdentifier' => 'existing-causation-id', 'correlationIdentifier' => 'overridden-correlation-id'];

        self::assertSame($mergedMetadata, $nestedEventWithMetadata->getMetadata());
    }

    /**
     * @test
     */
    public function eventIsUnwrappedWhenNestingEventsWithMetadata(): void
    {
        $eventWithMetadata = new EventWithCorrelationIdentifier($this->mockEvent, 'some-id');
        $nestedEventWithMetadata = new EventWithCorrelationIdentifier($eventWithMetadata, 'some-id');

        self::assertSame($this->mockEvent, $nestedEventWithMetadata->getEvent());
    }
}
