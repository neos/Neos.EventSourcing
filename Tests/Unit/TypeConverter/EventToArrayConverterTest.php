<?php
namespace Neos\EventSourcing\Tests\Unit\TypeConverter;

use Neos\EventSourcing\Tests\Unit\TypeConverter\Fixtures\EventWithDates;
use Neos\EventSourcing\Tests\Unit\TypeConverter\Fixtures\EventWithJsonSerializableProperties;
use Neos\EventSourcing\Tests\Unit\TypeConverter\Fixtures\EventWithObjects;
use Neos\EventSourcing\Tests\Unit\TypeConverter\Fixtures\EventWithSimpleTypes;
use Neos\EventSourcing\Tests\Unit\TypeConverter\Fixtures\JsonSerializableEvent;
use Neos\EventSourcing\TypeConverter\EventToArrayConverter;
use Neos\Flow\Tests\UnitTestCase;


require_once(__DIR__ . '/Fixtures/EventWithSimpleTypes.php');
require_once(__DIR__ . '/Fixtures/EventWithDates.php');
require_once(__DIR__ . '/Fixtures/EventWithObjects.php');
require_once(__DIR__ . '/Fixtures/JsonSerializableEvent.php');
require_once(__DIR__ . '/Fixtures/EventWithJsonSerializableProperties.php');

class EventToArrayConverterTest extends UnitTestCase
{
    /**
     * @var EventToArrayConverter
     */
    private $eventToArrayConverter;

    public function setUp()
    {
        $this->eventToArrayConverter = new EventToArrayConverter();
    }

    public function convertFromDataProvider()
    {
        $mockObject = new \stdClass();
        $mockObject->someString = 'someValue';
        $mockObject->someNestedObject = new \stdClass();
        $mockObject->someNestedObject->someString = 'someNestedValue';
        return [
            ['source' => new EventWithSimpleTypes('someValue', true, 123, 45.67), 'expectedResult' => ['someBoolean' => true, 'someFloat' => 45.67, 'someInteger' => 123, 'someString' => 'someValue']],
            ['source' => new EventWithDates(new \DateTime('1980-12-13T12:13:00+0100'), new \DateTimeImmutable('2017-06-01T11:07:00+0100'), new \DateTimeZone('Europe/Berlin')), 'expectedResult' => ['someDateTime' => '1980-12-13T12:13:00+0100', 'someDateTimeZone' => 'Europe/Berlin', 'someImmutableDateTime' => '2017-06-01T11:07:00+0100']],
            ['source' => new EventWithObjects($mockObject), 'expectedResult' => ['someObject' => ['someNestedObject' => [ 'someString' => 'someNestedValue'], 'someString' => 'someValue']]],
            ['source' => new JsonSerializableEvent(), 'expectedResult' => ['custom' => 'serialization']],
            ['source' => new EventWithJsonSerializableProperties(new JsonSerializableEvent()), 'expectedResult' => ['serializableProperty' => ['custom' => 'serialization']]],
        ];
    }

    /**
     * @test
     * @dataProvider convertFromDataProvider
     */
    public function convertFromTests($source, $expectedResult)
    {
        $actualResult = $this->eventToArrayConverter->convertFrom($source, 'array');
        $this->assertSame($expectedResult, $actualResult);
    }

}