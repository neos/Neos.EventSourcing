<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Functional\EventStore;

use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\Event\EventTypeResolver;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\Tests\Functional\EventStore\Fixture\MockDomainEvent;
use Neos\EventSourcing\Tests\Functional\EventStore\Fixture\MockDomainEvent2;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EventNormalizerTest extends FunctionalTestCase
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
        $this->mockEventTypeResolver = $this->getMockBuilder(EventTypeResolver::class)->disableOriginalConstructor()->getMock();
        $this->eventNormalizer = new EventNormalizer();
        $this->inject($this->eventNormalizer, 'eventTypeResolver', $this->mockEventTypeResolver);
    }

    /**
     * @param string $eventClassName
     * @param array $data
     * @param object $expectedResult
     * @test
     * @dataProvider denormalizeDataProvider
     */
    public function denormalizeTests(string $eventClassName, array $data, $expectedResult): void
    {
        $this->mockEventTypeResolver->method('getEventClassNameByType')->with('someEventType')->willReturn($eventClassName);
        $actualResult = $this->eventNormalizer->denormalize($data, 'someEventType');

        self::assertEquals($expectedResult, $actualResult);
    }

    public function denormalizeDataProvider(): \generator
    {
        yield [MockDomainEvent::class, ['string' => 'Some String'], new MockDomainEvent('Some String')];
        yield [MockDomainEvent2::class, ['string' => 'Some Other String'], new MockDomainEvent2('Some Other String')];
    }

    /**
     * @param DomainEventInterface $event
     * @param array $expectedResult
     * @test
     * @dataProvider normalizeDataProvider
     */
    public function normalizeTests(DomainEventInterface $event, array $expectedResult): void
    {
        $actualResult = $this->eventNormalizer->normalize($event);
        self::assertSame($expectedResult, $actualResult);
    }

    public function normalizeDataProvider(): \generator
    {
        yield [new MockDomainEvent('foo'), ['string' => 'foo']];
        yield [new MockDomainEvent2('bar'), ['string' => 'bar']];
    }

}
