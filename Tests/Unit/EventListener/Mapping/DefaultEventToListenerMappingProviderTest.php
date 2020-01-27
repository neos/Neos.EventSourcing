<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\EventListener\Mapping;

use Neos\EventSourcing\EventListener\EventListenerInterface;
use Neos\EventSourcing\EventListener\Exception\InvalidConfigurationException;
use Neos\EventSourcing\EventListener\Exception\InvalidEventListenerException;
use Neos\EventSourcing\EventListener\Mapping\DefaultEventToListenerMappingProvider;
use Neos\EventSourcing\EventListener\Mapping\EventToListenerMappings;
use Neos\EventSourcing\Tests\Unit\EventStore\Fixture\DummyEvent1;
use Neos\EventSourcing\Tests\Unit\EventStore\Fixture\DummyEvent2;
use Neos\EventSourcing\Tests\Unit\EventStore\Fixture\DummyEvent3;
use Neos\EventSourcing\Tests\Unit\EventStore\Fixture\SomethingHasHappened;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\MockObject\MockObject;

class DefaultEventToListenerMappingProviderTest extends UnitTestCase
{

    /**
     * @var ObjectManagerInterface|MockObject
     */
    private $mockObjectManager;

    /**
     * @var ConfigurationManager|MockObject
     */
    private $mockConfigurationManager;

    /**
     * @var ReflectionService|MockObject
     */
    private $mockReflectionService;

    /**
     * @var array
     */
    private $mockListenerParameters = [];

    /**
     * @var array
     */
    private $eventStoresConfiguration = [
        'eventStore1' => [
        ],
        'eventStore2' => [
        ],
    ];

    /**
     * @var array
     */
    private $mockListenerClassNames = [];

    public function setUp(): void
    {
        $this->mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();

        $this->mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $this->mockConfigurationManager->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.EventSourcing.EventStore.stores')->willReturnCallback(function () {
            return $this->eventStoresConfiguration;
        });

        $this->mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $this->mockReflectionService->method('isClassImplementationOf')->willReturnCallback(static function (string $className, string $interfaceName) {
            return is_subclass_of($className, $interfaceName, true);
        });
        $this->mockReflectionService->method('getMethodParameters')->willReturnCallback(function ($listenerClassName, $listenerMethodName) {
            return $this->mockListenerParameters[$listenerClassName][$listenerMethodName] ?? null;
        });

        $this->mockObjectManager->method('get')->willReturnCallback(function (string $objectName) {
            switch ($objectName) {
                case ReflectionService::class:
                    return $this->mockReflectionService;
                case ConfigurationManager::class:
                    return $this->mockConfigurationManager;
                default:
                    return null;
            }
        });

        $this->mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(EventListenerInterface::class)->willReturnCallback(function () {
            return $this->mockListenerClassNames;
        });
    }

    private function buildMockEventListener(array $listenerMethods, string $classNamePrefix = 'Mock_EventListener_'): string
    {
        $listenerClassName = $classNamePrefix . md5(uniqid('', true));
        $listenerCode = 'class ' . $listenerClassName . ' implements ' . EventListenerInterface::class . ' {';
        $this->mockListenerParameters[$listenerClassName] = [];
        foreach ($listenerMethods as $listenerMethodName => $params) {
            $paramCode = array_map(static function (array $param) {
                return $param['class'] . ' $' . $param['name'];
            }, $params);
            $listenerCode .= 'public function ' . $listenerMethodName . '(' . implode(', ', $paramCode) . ') {}';
            $this->mockListenerParameters[$listenerClassName][$listenerMethodName] = $params;
        }
        $listenerCode .= '}';

        eval($listenerCode);
        return $listenerClassName;
    }

    private function buildMockEventListenerForEvents(array $eventClassNames, string $classNamePrefix = 'Mock_EventListener_'): string
    {
        $listenerMethods = [];
        foreach ($eventClassNames as $eventClassName) {
            $eventShortName = substr($eventClassName, strrpos($eventClassName, '\\') + 1);
            $listenerMethods['when' . $eventShortName] = [['name' => 'event', 'class' => $eventClassName]];
        }
        return $this->buildMockEventListener($listenerMethods, $classNamePrefix);
    }

    private function assetMappings(array $expectedMappings, DefaultEventToListenerMappingProvider $mappingProvider): void
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $mappings = ObjectAccess::getProperty($mappingProvider, 'mappings', true);
        self::assertJsonStringEqualsJsonString(json_encode($expectedMappings), json_encode($mappings));
    }

    /**
     * @test
     */
    public function constructorFailsForListenersWithoutListenerMethods(): void
    {
        $this->mockListenerClassNames = [$this->buildMockEventListener(['someMethod' => []])];

        $this->expectException(InvalidEventListenerException::class);
        $this->expectExceptionCode(1498123537);

        /** @noinspection PhpUnhandledExceptionInspection */
        new DefaultEventToListenerMappingProvider($this->mockObjectManager);
    }

    /**
     * @test
     */
    public function constructorFailsForListenerMethodsWithoutParameter(): void
    {
        $this->mockListenerClassNames = [$this->buildMockEventListener(['whenSomethingHasHappened' => []])];

        $this->expectException(InvalidEventListenerException::class);
        $this->expectExceptionCode(1472500228);

        /** @noinspection PhpUnhandledExceptionInspection */
        new DefaultEventToListenerMappingProvider($this->mockObjectManager);
    }

    /**
     * @test
     */
    public function constructorFailsForListenerMethodsWithInvalidEventParameter(): void
    {
        $this->mockListenerClassNames = [$this->buildMockEventListener(['whenSomethingHasHappened' => [['name' => 'event', 'class' => \stdClass::class]]])];

        $this->expectException(InvalidEventListenerException::class);
        $this->expectExceptionCode(1472504443);

        /** @noinspection PhpUnhandledExceptionInspection */
        new DefaultEventToListenerMappingProvider($this->mockObjectManager);
    }

    /**
     * @test
     */
    public function constructorFailsForListenerMethodsWithInvalidRawEventParameter(): void
    {
        $this->mockListenerClassNames = [$this->buildMockEventListener(['whenSomethingHasHappened' => [['name' => 'event', 'class' => SomethingHasHappened::class], ['name' => 'rawEvent', 'class' => \stdClass::class]]])];

        $this->expectException(InvalidEventListenerException::class);
        $this->expectExceptionCode(1472504303);

        /** @noinspection PhpUnhandledExceptionInspection */
        new DefaultEventToListenerMappingProvider($this->mockObjectManager);
    }

    /**
     * @test
     */
    public function constructorFailsIfListenerMethodNameDoesNotMatchEventClassName(): void
    {
        $this->mockListenerClassNames = [$this->buildMockEventListener(['whenInvalidName' => [['name' => 'event', 'class' => SomethingHasHappened::class]]])];

        $this->expectException(InvalidEventListenerException::class);
        $this->expectExceptionCode(1476442394);

        /** @noinspection PhpUnhandledExceptionInspection */
        new DefaultEventToListenerMappingProvider($this->mockObjectManager);
    }

    public function invalidConfigurationDataProvider(): array
    {
        return [
            'unmatched listeners' => [
                'eventStoresConfiguration' => ['es1' => ['listeners' => ['Mock_EventListener_1.*' => []]], 'es2' => ['listeners' => ['Mock_EventListener_2.*' => []]]],
                'expectedException' => 1577532358,
            ],
            'missing es listeners' => [
                'eventStoresConfiguration' => ['es1' => ['listeners' => ['.*' => []]], 'es2' => ['listeners' => ['some-pattern' => false]]],
                'expectedException' => 1577534654,
            ],
            'overlapping listeners' => [
                'eventStoresConfiguration' => ['es1' => ['listeners' => ['.*' => []]], 'es2' => ['listeners' => ['Mock_EventListener_2.*' => []]]],
                'expectedException' => 1577532711,
            ],
            'listeners conf: invalid pattern' => [
                'eventStoresConfiguration' => ['es1' => ['listeners' => ['Mock_EventListener_1.*' => []]], 'es2' => ['listeners' => ['Mock_EventListener_(2|3).*' => [], 'Mock_EventListener_4.*' => []]]],
                'expectedException' => 1577533005,
            ],
        ];
    }

    /**
     * @param array $eventStoresConfiguration
     * @param int $expectedException
     * @test
     * @dataProvider invalidConfigurationDataProvider
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function invalidConfigurationTests(array $eventStoresConfiguration, int $expectedException): void
    {
        $this->mockListenerClassNames = [
            $this->buildMockEventListener(['whenSomethingHasHappened' => [['name' => 'event', 'class' => SomethingHasHappened::class]]], 'Mock_EventListener_1'),
            $this->buildMockEventListener(['whenSomethingHasHappened' => [['name' => 'event', 'class' => SomethingHasHappened::class]]], 'Mock_EventListener_2'),
            $this->buildMockEventListener(['whenSomethingHasHappened' => [['name' => 'event', 'class' => SomethingHasHappened::class]]], 'Mock_EventListener_3'),
        ];
        $this->eventStoresConfiguration = $eventStoresConfiguration;
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode($expectedException);

        /** @noinspection PhpUnhandledExceptionInspection */
        new DefaultEventToListenerMappingProvider($this->mockObjectManager);
    }

    /**
     * @test
     */
    public function constructorCreatesMappingsForDefaultConfiguration(): void
    {
        $this->eventStoresConfiguration = ['default' => ['listeners' => ['.*' => true]]];

        $listenerClassName1 = $this->buildMockEventListenerForEvents([DummyEvent1::class, DummyEvent2::class], 'Mock_EventListener_1');
        $listenerClassName2 = $this->buildMockEventListenerForEvents([DummyEvent2::class, DummyEvent3::class], 'Mock_EventListener_2');
        $this->mockListenerClassNames = [$listenerClassName1, $listenerClassName2];

        /** @noinspection PhpUnhandledExceptionInspection */
        $defaultMappingsProvider = new DefaultEventToListenerMappingProvider($this->mockObjectManager);
        $expectedMappings = [
            'default' => [
                ['eventClassName' => DummyEvent1::class, 'listenerClassName' => $listenerClassName1, 'options' => []],
                ['eventClassName' => DummyEvent2::class, 'listenerClassName' => $listenerClassName1, 'options' => []],
                ['eventClassName' => DummyEvent2::class, 'listenerClassName' => $listenerClassName2, 'options' => []],
                ['eventClassName' => DummyEvent3::class, 'listenerClassName' => $listenerClassName2, 'options' => []],
            ]
        ];
        $this->assetMappings($expectedMappings, $defaultMappingsProvider);
    }

    /**
     * @test
     */
    public function constructorCreatesMappingsForListenersWithCustomConfiguration(): void
    {
        $this->eventStoresConfiguration = ['default' => ['listeners' => ['Mock_EventListener_(1|3).*' => ['custom' => 'option'], 'Mock_EventListener_2.*' => true]]];

        $listenerClassName1 = $this->buildMockEventListenerForEvents([DummyEvent1::class, DummyEvent2::class], 'Mock_EventListener_1');
        $listenerClassName2 = $this->buildMockEventListenerForEvents([DummyEvent2::class, DummyEvent3::class], 'Mock_EventListener_2');
        $this->mockListenerClassNames = [$listenerClassName1, $listenerClassName2];

        /** @noinspection PhpUnhandledExceptionInspection */
        $defaultMappingsProvider = new DefaultEventToListenerMappingProvider($this->mockObjectManager);
        $expectedMappings = [
            'default' => [
                ['eventClassName' => DummyEvent1::class, 'listenerClassName' => $listenerClassName1, 'options' => ['custom' => 'option']],
                ['eventClassName' => DummyEvent2::class, 'listenerClassName' => $listenerClassName1, 'options' => ['custom' => 'option']],
                ['eventClassName' => DummyEvent2::class, 'listenerClassName' => $listenerClassName2, 'options' => []],
                ['eventClassName' => DummyEvent3::class, 'listenerClassName' => $listenerClassName2, 'options' => []],
            ]
        ];
        $this->assetMappings($expectedMappings, $defaultMappingsProvider);
    }

    /**
     * @test
     */
    public function constructorCreatesMappingsForMultipleStores(): void
    {
        $this->eventStoresConfiguration = ['default' => ['listeners' => ['Mock_EventListener_(1|3).*' => []]], 'es2' => ['listeners' => ['Mock_EventListener_2.*' => ['custom' => 'option']]]];

        $listenerClassName1 = $this->buildMockEventListenerForEvents([DummyEvent1::class, DummyEvent2::class], 'Mock_EventListener_1');
        $listenerClassName2 = $this->buildMockEventListenerForEvents([DummyEvent2::class, DummyEvent3::class], 'Mock_EventListener_2');
        $this->mockListenerClassNames = [$listenerClassName1, $listenerClassName2];

        /** @noinspection PhpUnhandledExceptionInspection */
        $defaultMappingsProvider = new DefaultEventToListenerMappingProvider($this->mockObjectManager);
        $expectedMappings = [
            'default' => [
                ['eventClassName' => DummyEvent1::class, 'listenerClassName' => $listenerClassName1, 'options' => []],
                ['eventClassName' => DummyEvent2::class, 'listenerClassName' => $listenerClassName1, 'options' => []],
            ],
            'es2' => [
                ['eventClassName' => DummyEvent2::class, 'listenerClassName' => $listenerClassName2, 'options' => ['custom' => 'option']],
                ['eventClassName' => DummyEvent3::class, 'listenerClassName' => $listenerClassName2, 'options' => ['custom' => 'option']],
            ]
        ];
        $this->assetMappings($expectedMappings, $defaultMappingsProvider);
    }

    /**
     * @test
     */
    public function constructorCreatesMappingsForMultipleStoresWithMultipleoptionss(): void
    {
        $this->eventStoresConfiguration = ['default' => ['listeners' => ['Mock_EventListener_1.*' => ['options' => '1'], 'Mock_EventListener_3.*' => ['options' => '2']]], 'es2' => ['listeners' => ['Mock_EventListener_2.*' => ['options' => '3']]]];

        $listenerClassName1 = $this->buildMockEventListenerForEvents([DummyEvent1::class, DummyEvent2::class], 'Mock_EventListener_1');
        $listenerClassName2 = $this->buildMockEventListenerForEvents([DummyEvent2::class, DummyEvent3::class], 'Mock_EventListener_2');
        $listenerClassName3 = $this->buildMockEventListenerForEvents([DummyEvent2::class, DummyEvent3::class], 'Mock_EventListener_3');
        $this->mockListenerClassNames = [$listenerClassName1, $listenerClassName2, $listenerClassName3];

        /** @noinspection PhpUnhandledExceptionInspection */
        $defaultMappingsProvider = new DefaultEventToListenerMappingProvider($this->mockObjectManager);
        $expectedMappings = [
            'default' => [
                ['eventClassName' => DummyEvent1::class, 'listenerClassName' => $listenerClassName1, 'options' => ['options' => '1']],
                ['eventClassName' => DummyEvent2::class, 'listenerClassName' => $listenerClassName1, 'options' => ['options' => '1']],
                ['eventClassName' => DummyEvent2::class, 'listenerClassName' => $listenerClassName3, 'options' => ['options' => '2']],
                ['eventClassName' => DummyEvent3::class, 'listenerClassName' => $listenerClassName3, 'options' => ['options' => '2']],
            ],
            'es2' => [
                ['eventClassName' => DummyEvent2::class, 'listenerClassName' => $listenerClassName2, 'options' => ['options' => '3']],
                ['eventClassName' => DummyEvent3::class, 'listenerClassName' => $listenerClassName2, 'options' => ['options' => '3']],
            ]
        ];
        $this->assetMappings($expectedMappings, $defaultMappingsProvider);
    }

    /**
     * @test
     */
    public function createThrowsExceptionIfTheGivenEventStoreIsNotConfigured(): void
    {
        $this->eventStoresConfiguration = ['default' => ['listeners' => ['Mock_EventListener_1.*' => ['options' => '1'], 'Mock_EventListener_3.*' => ['options' => '2']]], 'es2' => ['listeners' => ['Mock_EventListener_2.*' => ['options' => '3']]]];

        $listenerClassName1 = $this->buildMockEventListenerForEvents([DummyEvent1::class, DummyEvent2::class], 'Mock_EventListener_1');
        $listenerClassName2 = $this->buildMockEventListenerForEvents([DummyEvent2::class, DummyEvent3::class], 'Mock_EventListener_2');
        $listenerClassName3 = $this->buildMockEventListenerForEvents([DummyEvent2::class, DummyEvent3::class], 'Mock_EventListener_3');
        $this->mockListenerClassNames = [$listenerClassName1, $listenerClassName2, $listenerClassName3];

        /** @noinspection PhpUnhandledExceptionInspection */
        $defaultMappingsProvider = new DefaultEventToListenerMappingProvider($this->mockObjectManager);

        $this->expectException(\InvalidArgumentException::class);

        $defaultMappingsProvider->getMappingsForEventStore('nonExistingEventStore');
    }

    /**
     * @test
     */
    public function createReturnsEventPublisher(): void
    {
        $this->eventStoresConfiguration = ['default' => ['listeners' => ['Mock_EventListener_1.*' => ['options' => '1'], 'Mock_EventListener_3.*' => ['options' => '2']]], 'es2' => ['listeners' => ['Mock_EventListener_2.*' => ['options' => '3']]]];

        $listenerClassName1 = $this->buildMockEventListenerForEvents([DummyEvent1::class, DummyEvent2::class], 'Mock_EventListener_1');
        $listenerClassName2 = $this->buildMockEventListenerForEvents([DummyEvent2::class, DummyEvent3::class], 'Mock_EventListener_2');
        $listenerClassName3 = $this->buildMockEventListenerForEvents([DummyEvent2::class, DummyEvent3::class], 'Mock_EventListener_3');
        $this->mockListenerClassNames = [$listenerClassName1, $listenerClassName2, $listenerClassName3];

        /** @noinspection PhpUnhandledExceptionInspection */
        $defaultMappingsProvider = new DefaultEventToListenerMappingProvider($this->mockObjectManager);

        $eventPublisher = $defaultMappingsProvider->getMappingsForEventStore('es2');
        self::assertInstanceOf(EventToListenerMappings::class, $eventPublisher);
    }

}
