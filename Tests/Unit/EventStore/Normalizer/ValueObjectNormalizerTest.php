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
    public function supportsDenormalizationTests($sourceData, string $className): void
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
    public function denormalizeTests($sourceData, string $className): void
    {
        $normalizer = new ValueObjectNormalizer();
        $this->assertInstanceOf(
            $className,
            $normalizer->denormalize($sourceData, $className)
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
}
