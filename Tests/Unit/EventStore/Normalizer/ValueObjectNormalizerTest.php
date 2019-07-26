<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Normalizer;

use Neos\EventSourcing\EventStore\Normalizer\ValueObjectNormalizer;
use Neos\Flow\Tests\UnitTestCase;

class ValueObjectNormalizerTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider provideSourceDataAndClassNames
     */
    public function ValueObjectNormalizer_supports_denormalization_of($sourceData, string $className): void
    {
        $normalizer = new ValueObjectNormalizer();
        $this->assertTrue(
            $normalizer->supportsDenormalization($sourceData, $className)
        );
    }

    /**
     * @test
     * @dataProvider provideSourceDataAndClassNames
     */
    public function ValueObjectNormalizer_creates_instances_of_a_given_type($sourceData, string $className): void
    {
        $normalizer = new ValueObjectNormalizer();
        $this->assertInstanceOf(
            $className,
            $normalizer->denormalize($sourceData, $className)
        );
    }

    public function provideSourceDataAndClassNames()
    {
        yield 'integer' => [0, IntegerBasedValueObject::class];
        yield 'string' => ['', StringBasedValueObject::class];
        yield 'boolean' => [true, BooleanBasedValueObject::class];
        yield 'float' => [0.0, FloatBasedValueObject::class];
        yield 'array' => [[], ArrayBasedValueObject::class];
    }
}

class IntegerBasedValueObject
{
    public static function fromInteger(int $value): self
    {
        return new self();
    }
}

class StringBasedValueObject
{
    public static function fromString(string $value): self
    {
        return new self();
    }
}

class BooleanBasedValueObject
{
    public static function fromBoolean(bool $value): self
    {
        return new self();
    }
}

class FloatBasedValueObject
{
    public static function fromFloat(float $value): self
    {
        return new self();
    }
}

class ArrayBasedValueObject
{
    public static function fromArray(array $value): self
    {
        return new self();
    }
}
