<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Functional\EventStore;

use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\Event\EventTypeResolverInterface;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\Tests\Functional\EventStore\Fixture\MockDomainEvent;
use Neos\EventSourcing\Tests\Functional\EventStore\Fixture\MockDomainEvent2;
use Neos\EventSourcing\Tests\Functional\EventStore\Fixture\MockDomainEvent3;
use Neos\EventSourcing\Tests\Functional\EventStore\Fixture\MockValueObject;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;

class EventNormalizerTest extends FunctionalTestCase
{
    /**
     * @var EventNormalizer
     */
    private $eventNormalizer;

    /**
     * @var EventTypeResolverInterface|MockObject
     */
    private $mockEventTypeResolver;

    public function setUp(): void
    {
        $this->mockEventTypeResolver = $this->getMockBuilder(EventTypeResolverInterface::class)->getMock();
        $this->eventNormalizer = new EventNormalizer($this->mockEventTypeResolver);
    }

    /**
     * @param string $eventClassName
     * @param array $data
     * @param object $expectedResult
     * @test
     * @dataProvider denormalizeDataProvider
     * @throws SerializerException
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
        yield [MockDomainEvent3::class, ['value' => 'Yet another String'], new MockDomainEvent3(new MockValueObject('Yet another String'))];
    }

    /**
     * @param DomainEventInterface $event
     * @param array $expectedResult
     * @test
     * @dataProvider normalizeDataProvider
     * @throws SerializerException
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
        yield [new MockDomainEvent3(new MockValueObject('baz')), ['value' => 'baz']];
    }

}
