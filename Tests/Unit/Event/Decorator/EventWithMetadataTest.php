<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\Event\Decorator;

use Neos\EventSourcing\Event\Decorator\EventDecoratorUtilities;
use Neos\EventSourcing\Event\Decorator\EventWithMetadata;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EventWithMetadataTest extends UnitTestCase
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
    public function originalEventCanBeRetrieved(): void
    {
        $eventWithMetadata = new EventWithMetadata($this->mockEvent, []);
        self::assertSame($this->mockEvent, $eventWithMetadata->getEvent());
    }

    /**
     * @test
     */
    public function metadataCanBeRetrieved(): void
    {
        $someMetadata = ['foo' => ['bar' => 'Baz']];
        $eventWithMetadata = new EventWithMetadata($this->mockEvent, $someMetadata);
        self::assertSame($someMetadata, $eventWithMetadata->getMetadata());
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

        self::assertSame($mergedMetadata, $nestedEventWithMetadata->getMetadata());
    }

    /**
     * @test
     */
    public function eventIsUnwrappedWhenNestingEventsWithMetadata(): void
    {
        $eventWithMetadata = new EventWithMetadata($this->mockEvent, []);
        $nestedEventWithMetadata = new EventWithMetadata($eventWithMetadata, []);

        self::assertSame($this->mockEvent, EventDecoratorUtilities::extractUndecoratedEvent($nestedEventWithMetadata));
    }
}
