<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\EventStore;

use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\Tests\UnitTestCase;

class EventStreamTest extends UnitTestCase
{

    /**
     * @test
     */
    public function fromStringThrowsExceptionIfValueIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        StreamName::fromString(' ');
    }

    /**
     * @test
     */
    public function fromStringThrowsExceptionIfValueStartsWithDollarSign(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        StreamName::fromString('$some-stream');
    }

    /**
     * @test
     */
    public function fromStringTrimsValue(): void
    {
        $streamName = StreamName::fromString('  some-stream  ');
        self::assertSame('some-stream', (string)$streamName);
    }

    /**
     * @test
     */
    public function fromCategoryThrowsExceptionIfValueIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        StreamName::forCategory(' ');
    }

    /**
     * @test
     */
    public function fromCategoryThrowsExceptionIfValueStartsWithDollarSign(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        StreamName::forCategory('$some-category');
    }

    /**
     * @test
     */
    public function fromCategoryTrimsValue(): void
    {
        $streamName = StreamName::forCategory('  some-category  ');
        self::assertSame('$ce-some-category', (string)$streamName);
    }

    /**
     * @test
     */
    public function forCorrelationIdThrowsExceptionIfValueIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        StreamName::forCorrelationId(' ');
    }

    /**
     * @test
     */
    public function forCorrelationIdThrowsExceptionIfValueStartsWithDollarSign(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        StreamName::forCorrelationId('$some-correlation-id');
    }

    /**
     * @test
     */
    public function forCorrelationIdTrimsValue(): void
    {
        $streamName = StreamName::forCorrelationId('  some-correlation-id  ');
        self::assertSame('$correlation-some-correlation-id', (string)$streamName);
    }

    /**
     * @test
     */
    public function allReturnsAllStream(): void
    {
        $streamName = StreamName::all();
        self::assertSame('$all', (string)$streamName);
    }

    public function classificationDataProvider(): \generator
    {
        yield [
            'streamName' => StreamName::fromString('normal-stream'),
            'isVirtual' => false,
            'isAll' => false,
            'isCategory' => false,
            'isCorrelationId' => false,
        ];
        yield [
            'streamName' => StreamName::forCategory('some-category'),
            'isVirtual' => true,
            'isAll' => false,
            'isCategory' => true,
            'isCorrelationId' => false,
        ];
        yield [
            'streamName' => StreamName::forCorrelationId('some-id'),
            'isVirtual' => true,
            'isAll' => false,
            'isCategory' => false,
            'isCorrelationId' => true,
        ];
        yield [
            'streamName' => StreamName::all(),
            'isVirtual' => true,
            'isAll' => true,
            'isCategory' => false,
            'isCorrelationId' => false,
        ];
    }

    /**
     * @param StreamName $streamName
     * @param bool $isVirtual
     * @param bool $isAll
     * @param bool $isCategory
     * @param bool $isCorrelationId
     * @test
     * @dataProvider classificationDataProvider
     */
    public function classificationTests(StreamName $streamName, bool $isVirtual, bool $isAll, bool $isCategory, bool $isCorrelationId): void
    {
        self::assertSame($isVirtual, $streamName->isVirtualStream());
        self::assertSame($isAll, $streamName->isAllStream());
        self::assertSame($isCategory, $streamName->isCategoryStream());
        self::assertSame($isCorrelationId, $streamName->isCorrelationIdStream());
    }

    /**
     * @test
     */
    public function getCategoryNameThrowsExceptionIfNoCategoryStream(): void
    {
        $this->expectException(\RuntimeException::class);
        $streamName = StreamName::fromString('some-stream');
        $streamName->getCategoryName();
    }

    /**
     * @test
     */
    public function getCategoryNameReturnsCategoryNameOfCategoryStream(): void
    {
        $streamName = StreamName::forCategory('Some-Category');
        self::assertSame('Some-Category', $streamName->getCategoryName());
    }

    /**
     * @test
     */
    public function getCorrelationIdThrowsExceptionIfNoCorrelationIdStream(): void
    {
        $this->expectException(\RuntimeException::class);
        $streamName = StreamName::fromString('some-stream');
        $streamName->getCorrelationId();
    }

    /**
     * @test
     */
    public function getCorrelationIdReturnsCorrelationIdOfCorrelationIdStream(): void
    {
        $streamName = StreamName::forCorrelationId('some-correlation-id');
        self::assertSame('some-correlation-id', $streamName->getCorrelationId());
    }

    /**
     * @test
     */
    public function streamNameInstancesAreConstant(): void
    {
        $streamName1 = StreamName::fromString(' some-stream');
        $streamName2 = StreamName::fromString('some-stream ');
        self::assertSame($streamName1, $streamName2);
    }
}
