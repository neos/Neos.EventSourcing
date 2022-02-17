<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\EventStore;

use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\Event\EventTypeResolver;
use Neos\EventSourcing\Event\EventTypeResolverInterface;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\Tests\Unit\EventStore\Fixture\ArrayValueObject;
use Neos\EventSourcing\Tests\Unit\EventStore\Fixture\BackedEnum;
use Neos\EventSourcing\Tests\Unit\EventStore\Fixture\BooleanValueObject;
use Neos\EventSourcing\Tests\Unit\EventStore\Fixture\EventWithBackedEnum;
use Neos\EventSourcing\Tests\Unit\EventStore\Fixture\EventWithDateTime;
use Neos\EventSourcing\Tests\Unit\EventStore\Fixture\EventWithValueObjects;
use Neos\EventSourcing\Tests\Unit\EventStore\Fixture\FloatValueObject;
use Neos\EventSourcing\Tests\Unit\EventStore\Fixture\IntegerValueObject;
use Neos\EventSourcing\Tests\Unit\EventStore\Fixture\StringValueObject;
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

    public function normalizeDataProvider(): \generator
    {
        $dateTimeImmutable = new \DateTimeImmutable('1980-12-13');
        $dateTime = new \DateTime('1980-12-13 15:34:19');
        $jsonSerializable = new class implements \JsonSerializable { public function jsonSerialize(): array { return ['foo' => 'bar'];}};
        yield 'dateTimeImmutable' => ['value' => $dateTimeImmutable, 'expectedResult' => $dateTimeImmutable->format(\DateTimeInterface::RFC3339)];
        yield 'dateTime' => ['value' => $dateTime, 'expectedResult' => $dateTime->format(\DateTimeInterface::RFC3339)];
        yield 'jsonSerializable' => ['value' => $jsonSerializable, 'expectedResult' => ['foo' => 'bar']];
    }

    /**
     * @test
     * @dataProvider normalizeDataProvider
     */
    public function normalizeTests($value, $expectedResult): void
    {
        $event = $this->getMockBuilder(DomainEventInterface::class)->addMethods(['getProperty'])->getMock();
        /** @noinspection MockingMethodsCorrectnessInspection */
        $event->method('getProperty')->willReturn($value);
        $result = $this->eventNormalizer->normalize($event);
        self::assertSame(['property' => $expectedResult], $result);
    }

    public function denormalizeDataProvider(): \generator
    {
        $dateTimeImmutable = new \DateTimeImmutable('1980-12-13');
        $dateTime = new \DateTime('1980-12-13 15:34:19');
        $array = ['foo' => 'bar', 'Bar' => ['nested' => 'foos']];
        $string = 'Some string with späcial characterß';
        $integer = 42;
        $float = 42.987;
        $boolean = true;
        yield 'dateTimeImmutable' => ['data' => ['date' => $dateTimeImmutable->format(\DateTimeInterface::RFC3339)], 'expectedResult' => new EventWithDateTime($dateTimeImmutable)];
        yield 'dateTime' => ['data' => ['date' => $dateTime->format(\DateTimeInterface::RFC3339)], 'expectedResult' => new EventWithDateTime($dateTime)];
        yield 'valueObjects' => ['data' => compact('array', 'string', 'integer', 'float', 'boolean'), 'expectedResult' => new EventWithValueObjects(ArrayValueObject::fromArray($array), StringValueObject::fromString($string), IntegerValueObject::fromInteger($integer), FloatValueObject::fromFloat($float), BooleanValueObject::fromBoolean($boolean))];
    }

    /**
     * @test
     * @dataProvider denormalizeDataProvider
     */
    public function denormalizeTests(array $data, object $expectedResult): void
    {
        $this->mockEventTypeResolver->method('getEventClassNameByType')->with('Some.Event:Type')->willReturn(get_class($expectedResult));
        $result = $this->eventNormalizer->denormalize($data, 'Some.Event:Type');
        self::assertObjectEquals($expectedResult, $result);
    }

    /**
     * @test
     */
    public function normalizeSupportsBackedEnums(): void
    {
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Backed enums are only available with PHP 8.1+');
        }
        $event = new EventWithBackedEnum(BackedEnum::Hearts);
        $result = $this->eventNormalizer->normalize($event);
        self::assertSame(['enum' => 'H'], $result);
    }

    /**
     * @test
     */
    public function denormalizeSupportsBackedEnums(): void
    {
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Backed enums are only available with PHP 8.1+');
        }
        $this->mockEventTypeResolver->method('getEventClassNameByType')->with('Some.Event:Type')->willReturn(EventWithBackedEnum::class);
        /** @var EventWithBackedEnum $event */
        $event = $this->eventNormalizer->denormalize(['enum' => 'C'], 'Some.Event:Type');
        self::assertSame(BackedEnum::Clubs, $event->getEnum());
    }
}
