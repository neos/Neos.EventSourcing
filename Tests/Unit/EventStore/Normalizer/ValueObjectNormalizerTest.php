<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Normalizer;

use Neos\EventSourcing\EventStore\Normalizer\ValueObjectNormalizer;
use Neos\Flow\Tests\UnitTestCase;

class ValueObjectNormalizerTest extends UnitTestCase
{
    /**
     * @var ValueObjectNormalizer
     */
    private $valueObjectNormalizer;

    public function setUp(): void
    {
        $this->valueObjectNormalizer = new ValueObjectNormalizer();
    }

    /**
     * @test
     * @dataProvider provideSourceDataAndClassNames
     */
    public function supportsDenormalizationTests($sourceData, string $className): void
    {
        $this->assertTrue(
            $this->valueObjectNormalizer->supportsDenormalization($sourceData, $className)
        );
    }

    /**
     * @test
     * @dataProvider provideSourceDataAndClassNames
     */
    public function denormalizeTests($sourceData, string $className): void
    {
        $this->assertInstanceOf(
            $className,
            $this->valueObjectNormalizer->denormalize($sourceData, $className)
        );
    }

    public function provideSourceDataAndClassNames()
    {
        yield 'integer' => [0, Fixture\IntegerBasedValueObject::class];
        yield 'string' => ['', Fixture\StringBasedValueObject::class];
        yield 'boolean' => [true, Fixture\BooleanBasedValueObject::class];
        yield 'float' => [0.0, Fixture\FloatBasedValueObject::class];
        yield 'array' => [[], Fixture\ArrayBasedValueObject::class];
    }

    /**
     * @test
     */
    public function supportsDenormalizationReturnsFalseForArrayValueObjectsWithoutNamedConstructor(): void
    {
        self::assertFalse($this->valueObjectNormalizer->supportsDenormalization(['some' => 'array'], Fixture\InvalidArrayBasedValueObject::class));
    }

    /**
     * @test
     */
    public function denormalizeFailsForArrayValueObjectsWithoutNamedConstructor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->valueObjectNormalizer->denormalize(['some' => 'array'], Fixture\InvalidArrayBasedValueObject::class);
    }
}
