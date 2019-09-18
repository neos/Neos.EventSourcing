<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\Event;

use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DecoratedEventTest extends UnitTestCase
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
    public function addCausationIdentifierDoesntAcceptEmptyCausationId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DecoratedEvent::addCausationIdentifier($this->mockEvent, '');
    }

    /**
     * @test
     */
    public function addCausationIdentifierDoesntAcceptCausationIdExceedingMaxLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DecoratedEvent::addCausationIdentifier($this->mockEvent, str_repeat('x', 256));
    }

    /**
     * @test
     */
    public function causationIdentifierCanBeRetrieved(): void
    {
        $someCausationId = 'some-id';
        $eventWithMetadata = DecoratedEvent::addCausationIdentifier($this->mockEvent, $someCausationId);
        $metadata = $eventWithMetadata->getMetadata();
        self::assertSame($someCausationId, $metadata['causationIdentifier']);
    }

    /**
     * @test
     */
    public function addCorrelationIdentifierDoesntAcceptEmptyCorrelationId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DecoratedEvent::addCausationIdentifier($this->mockEvent, '');
    }

    /**
     * @test
     */
    public function addCorrelationIdentifierDoesntAcceptCausationIdExceedingMaxLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DecoratedEvent::addCorrelationIdentifier($this->mockEvent, str_repeat('x', 256));
    }

    /**
     * @test
     */
    public function addMetadataAllowsEmptyArray(): void
    {
        DecoratedEvent::addMetadata($this->mockEvent, []);
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function metadataCanBeRetrieved(): void
    {
        $someMetadata = [
            'foo' => 'bar',
            'baz' => 'foos',
        ];
        $eventWithMetadata = DecoratedEvent::addMetadata($this->mockEvent, $someMetadata);
        self::assertSame($someMetadata, $eventWithMetadata->getMetadata());
    }

    /**
     * @test
     */
    public function correlationIdentifierCanBeRetrieved(): void
    {
        $someCorrelationId = 'some-id';
        $eventWithMetadata = DecoratedEvent::addCorrelationIdentifier($this->mockEvent, $someCorrelationId);
        $metadata = $eventWithMetadata->getMetadata();
        self::assertSame($someCorrelationId, $metadata['correlationIdentifier']);
    }

    /**
     * @test
     */
    public function identifierIsNullByDefault(): void
    {
        $decoratedEvent = DecoratedEvent::addMetadata($this->mockEvent, ['foo' => 'bar']);
        self::assertNull($decoratedEvent->getIdentifier());
    }

    /**
     * @test
     */
    public function identifierCanBeRetrieved(): void
    {
        $decoratedEvent = DecoratedEvent::addIdentifier($this->mockEvent, 'some-identifier');
        self::assertSame('some-identifier', $decoratedEvent->getIdentifier());
    }

    /**
     * @test
     */
    public function hasIdentifierIsFalseByDefault(): void
    {
        $decoratedEvent = DecoratedEvent::addMetadata($this->mockEvent, ['foo' => 'bar']);
        self::assertFalse($decoratedEvent->hasIdentifier());
    }

    /**
     * @test
     */
    public function hasIdentifierIsTrueIfIdentifierIsSet(): void
    {
        $decoratedEvent = DecoratedEvent::addIdentifier($this->mockEvent, 'some-identifier');
        self::assertTrue($decoratedEvent->hasIdentifier());
    }

    /**
     * @test
     */
    public function addIdentifierDoesntAcceptEmptyCorrelationId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DecoratedEvent::addIdentifier($this->mockEvent, '');
    }

    /**
     * @test
     */
    public function addIdentifierDoesntAcceptCausationIdExceedingMaxLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DecoratedEvent::addIdentifier($this->mockEvent, str_repeat('x', 256));
    }

    /**
     * @test
     */
    public function metadataIsMergedWhenNestingDecoratedEvents(): void
    {
        $someMetadata = ['foo' => ['bar' => 'Baz', 'foos' => 'bars'], 'causationIdentifier' => 'existing-causation-id', 'correlationIdentifier' => 'existing-causation-id'];
        $decoratedEvent = DecoratedEvent::addMetadata($this->mockEvent, $someMetadata);

        $nestedDecoratedEvent = DecoratedEvent::addCausationIdentifier(DecoratedEvent::addIdentifier(DecoratedEvent::addMetadata($decoratedEvent, ['additional' => 'added', 'foo' => ['bar' => 'Baz overridden']]), 'some-id'), 'overridden-causation-id');

        $mergedMetadata = ['foo' => ['bar' => 'Baz overridden', 'foos' => 'bars'], 'causationIdentifier' => 'overridden-causation-id', 'correlationIdentifier' => 'existing-causation-id', 'additional' => 'added'];

        self::assertSame($mergedMetadata, $nestedDecoratedEvent->getMetadata());
    }

    /**
     * @test
     */
    public function eventIsUnwrappedWhenNestingDecoratedEvents(): void
    {
        $decoratedEvent = DecoratedEvent::addCausationIdentifier($this->mockEvent, 'some-id');
        $nestedDecoratedEvent = DecoratedEvent::addCausationIdentifier($decoratedEvent,'some-id');

        self::assertSame($this->mockEvent, $nestedDecoratedEvent->getWrappedEvent());
    }
}
