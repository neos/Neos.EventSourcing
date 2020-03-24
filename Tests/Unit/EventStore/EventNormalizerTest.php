<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\EventStore;

use Neos\EventSourcing\Event\EventTypeResolver;
use Neos\EventSourcing\Event\EventTypeResolverInterface;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EventNormalizerTest extends UnitTestCase
{

    /**
     * @var EventNormalizer
     */
    private $eventNormalizer;

    /**
     * @var EventTypeResolver|MockObject
     */
    private $mockEventTypeResolver;

    public function setUp(): void
    {
        $this->mockEventTypeResolver = $this->getMockBuilder(EventTypeResolverInterface::class)->getMock();
        $this->eventNormalizer = new EventNormalizer($this->mockEventTypeResolver);
    }

    /**
     * @test
     */
    public function normalizeExtractsPayloadFromArrayBasedEvent(): void
    {
        $mockData = ['some' => 'data'];
        $event = new Fixture\ArrayBasedEvent($mockData);
        $result = $this->eventNormalizer->normalize($event);
        self::assertSame(['data' => $mockData], $result);
    }

    /**
     * see https://github.com/neos/Neos.EventSourcing/issues/233
     *
     * @test
     */
    public function denormalizeConstructsArrayBasedEventWithCorrectPayload(): void
    {
        $mockData = ['some' => 'data'];
        $normalizedEvent = ['data' => $mockData];

        $this->mockEventTypeResolver->method('getEventClassNameByType')->with('Some.Event:Type')->willReturn(Fixture\ArrayBasedEvent::class);

        /** @var Fixture\ArrayBasedEvent $event */
        $event = $this->eventNormalizer->denormalize($normalizedEvent, 'Some.Event:Type');
        self::assertSame($mockData, $event->getData());
    }
}
