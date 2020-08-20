<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\EventStore\Storage\InMemory;

use Neos\EventSourcing\EventStore\Storage\InMemory\InMemoryStreamIterator;
use Neos\Flow\Tests\UnitTestCase;
use Ramsey\Uuid\Uuid;

class InMemoryStreamIteratorTest extends UnitTestCase
{
    /**
     * @var InMemoryStreamIterator
     */
    private $iterator;

    /**
     * @throws
     */
    public function setUp()
    {
        $this->iterator = new InMemoryStreamIterator([
            [
                'sequencenumber' => 1,
                'type' => 'FooEventType',
                'payload' => json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR, 512),
                'metadata' => json_encode([], JSON_THROW_ON_ERROR, 512),
                'recordedat' => '2020-08-17',
                'stream' => 'FooStreamName',
                'version' => 1,
                'id' => Uuid::uuid4()->toString()
            ],
            [
                'sequencenumber' => 2,
                'type' => 'FooEventType',
                'payload' => json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR, 512),
                'metadata' => json_encode([], JSON_THROW_ON_ERROR, 512),
                'recordedat' => '2020-08-17',
                'stream' => 'FooStreamName',
                'version' => 2,
                'id' => Uuid::uuid4()->toString()
            ]
        ]);
    }

    /**
     * @test
     * @throws
     */
    public function setEventRecordsRejectsInvalidDate(): void
    {
        $iterator = new InMemoryStreamIterator([
            [
                'sequencenumber' => 1,
                'type' => 'FooEventType',
                'payload' => json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR, 512),
                'metadata' => json_encode([], JSON_THROW_ON_ERROR, 512),
                'recordedat' => 'invalid-date',
                'stream' => 'FooStreamName',
                'version' => 1,
                'id' => Uuid::uuid4()->toString()
            ]
        ]);

        $this->expectExceptionCode(1597843669);
        $iterator->current();
    }

    /**
     * @test
     * @throws
     */
    public function canSetEventRecordsAndGetRawEvents(): void
    {
        $rawEvent = $this->iterator->current();
        $this->assertSame($rawEvent->getSequenceNumber(), 1);
        $this->assertSame($rawEvent->getType(), 'FooEventType');
        $this->assertSame($rawEvent->getRecordedAt()->format('Y-m-d'), '2020-08-17');
        $this->assertSame((string)$rawEvent->getStreamName(), 'FooStreamName');
    }

    /**
     * @test
     * @throws
     */
    public function providesIteratorFunctions(): void
    {
        $this->assertSame($this->iterator->key(), 1);

        $this->iterator->next();
        $this->assertSame($this->iterator->key(), 2);
        $this->assertSame($this->iterator->current()->getSequenceNumber(), 2);

        $this->assertTrue($this->iterator->valid());
        $this->iterator->next();
        $this->assertFalse($this->iterator->valid());

        $this->iterator->rewind();
        $this->assertTrue($this->iterator->valid());
        $this->assertSame($this->iterator->key(), 1);

        $this->iterator->rewind();
        $this->assertTrue($this->iterator->valid());
        $this->assertSame($this->iterator->key(), 1);
    }
}
