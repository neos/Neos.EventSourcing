<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\EventStore;

use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\EventSourcing\EventStore\Exception\StorageConfigurationException;
use Neos\EventSourcing\EventStore\Storage\EventStorageInterface;
use Neos\EventSourcing\EventStore\StreamName;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EventStoreManagerTest extends UnitTestCase
{

    /**
     * @var EventStoreManager
     */
    private $eventStoreManager;

    /**
     * @var EventStorageInterface|MockObject
     */
    private $mockEventStorage;

    /**
     * @var EventStore|MockObject
     */
    private $mockEventStore;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    private $mockObjectManager;

    public function setUp(): void
    {
        $this->mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();

        $this->mockEventStorage = $this->getMockBuilder(EventStorageInterface::class)->getMock();
        $this->mockEventStore = new EventStore($this->mockEventStorage);

        $mockConfiguration = [
            'fallbackStore' => [
                'storage' => 'fallbackStoreStorage',
                'boundedContexts' => [
                    '*' => true,
                    'Bounded.Context1' => true,
                ],
            ],
            'eventStore2' => [
                'storage' => 'eventStore2Storage',
                'boundedContexts' => [
                    'Bounded.Context2' => true,
                ],
            ],
            'eventStore3' => [
                'storage' => 'eventStore3Storage',
                'boundedContexts' => [
                    'Bounded.Context3' => true,
                    'Bounded.Context2.Sub' => true,
                    'Bounded.Context23' => true,
                    'Inactive' => false,
                ],
            ],
        ];
        $this->eventStoreManager = new EventStoreManager($this->mockObjectManager, $mockConfiguration);
    }

    /**
     * @test
     */
    public function getEventStoreThrowsExceptionForEventStoreConfigurationsWithoutBoundedContextTarget(): void
    {
        $this->expectException(StorageConfigurationException::class);
        $this->expectExceptionCode(1479213813);
        $mockConfiguration = [
            'someStore' => [
                'storage' => 'Foo',
                'boundedContexts' => [],
            ],
        ];
        $eventStoreManager = new EventStoreManager($this->mockObjectManager, $mockConfiguration);
        $eventStoreManager->getEventStore('someStore');
    }

    /**
     * @test
     */
    public function getEventStoreThrowsExceptionIfNoFallbackStoreIsConfigured(): void
    {
        $this->expectException(StorageConfigurationException::class);
        $this->expectExceptionCode(1479214520);
        $mockConfiguration = [];
        $eventStoreManager = new EventStoreManager($this->mockObjectManager, $mockConfiguration);
        $eventStoreManager->getEventStore('someStore');
    }

    /**
     * @test
     */
    public function getEventStoreThrowsExceptionForEventStoreConfigurationsWithOverlappingBoundedContexts(): void
    {
        $this->expectException(StorageConfigurationException::class);
        $this->expectExceptionCode(1492434176);
        $mockConfiguration = [
            'someStore' => [
                'storage' => 'Foo',
                'boundedContexts' => [
                    'Bounded:Context1' => true,
                    'Bounded:Context2' => true,
                    '*' => true,
                ],
            ],
            'someOtherStore' => [
                'storage' => 'Bar',
                'boundedContexts' => [
                    'Bounded:Context2' => true,
                    'Bounded:Context3' => true,
                ],
            ],
        ];
        $eventStoreManager = new EventStoreManager($this->mockObjectManager, $mockConfiguration);
        $eventStoreManager->getEventStore('someStore');
    }

    /**
     * @test
     */
    public function getEventStoreThrowsExceptionIfTheRequestedEventStoreIsNotConfigured(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1492610857);
        $mockConfiguration = [
            'someStore' => [
                'storage' => 'Foo',
                'boundedContexts' => [
                    '*' => true,
                ],
            ],
            'someOtherStore' => [
                'storage' => 'Bar',
                'boundedContexts' => [
                    'SomeBounded:Context' => true,
                ],
            ],
        ];
        $eventStoreManager = new EventStoreManager($this->mockObjectManager, $mockConfiguration);
        $eventStoreManager->getEventStore('someNonExistingStore');
    }

    /**
     * @test
     */
    public function getEventStoreThrowsExceptionIfNoStorageIsConfigured(): void
    {
        $this->expectException(StorageConfigurationException::class);
        $this->expectExceptionCode(1492610902);
        $mockConfiguration = [
            'someStore' => [
                'boundedContexts' => [
                    '*' => true,
                ],
            ],
        ];
        $eventStoreManager = new EventStoreManager($this->mockObjectManager, $mockConfiguration);
        $eventStoreManager->getEventStore('someStore');
    }

    /**
     * @test
     */
    public function getEventStoreThrowsExceptionIfConfiguredStorageIsNoEventStorageInterface(): void
    {
        $this->expectException(StorageConfigurationException::class);
        $this->expectExceptionCode(1492610908);
        $mockConfiguration = [
            'someStore' => [
                'storage' => 'EventStorageClassName',
                'boundedContexts' => [
                    '*' => true,
                ],
            ],
        ];
        $eventStoreManager = new EventStoreManager($this->mockObjectManager, $mockConfiguration);

        $this->mockObjectManager->expects(self::once())->method('get')->with('EventStorageClassName')->willReturn(new \stdClass());
        $eventStoreManager->getEventStore('someStore');
    }

    /**
     * @test
     */
    public function getEventStoreReturnsAnInstanceOfEventStoreWithTheConfiguredStorage(): void
    {
        $mockStorageOptions = ['foo' => 'Bar'];
        $mockConfiguration = [
            'someStore' => [
                'storage' => get_class($this->mockEventStorage),
                'storageOptions' => $mockStorageOptions,
                'boundedContexts' => [
                    '*' => true,
                ],
            ],
        ];
        $eventStoreManager = new EventStoreManager($this->mockObjectManager, $mockConfiguration);

        $this->mockObjectManager->expects(self::at(0))->method('get')->with(get_class($this->mockEventStorage), $mockStorageOptions)->willReturn($this->mockEventStorage);
        $this->mockObjectManager->expects(self::at(1))->method('get')->with(EventStore::class, $this->mockEventStorage)->willReturn($this->mockEventStore);

        $eventStoreManager->getEventStore('someStore');
        $eventStore = $eventStoreManager->getEventStore('someStore');

        $this->mockEventStorage->expects(self::once())->method('setup');
        $eventStore->setup();
    }

    /**
     * @test
     */
    public function eventStoresAreInstantiatedLazily(): void
    {
        $mockConfiguration = [
            'eventStore1' => [
                'storage' => 'EventStorage1',
                'boundedContexts' => [
                    '*' => true,
                ],
            ],
            'eventStore2' => [
                'storage' => 'EventStorage2',
                'boundedContexts' => [
                    'Foo' => true,
                ]
            ],
        ];
        $eventStoreManager = new EventStoreManager($this->mockObjectManager, $mockConfiguration);

        $this->mockObjectManager->expects(self::atLeastOnce())->method('get')->willReturnCallback(function ($className) {
            switch ($className) {
                case EventStore::class:
                    return $this->mockEventStore;
                case 'EventStorage2':
                    return $this->mockEventStorage;
                default:
                    $this->fail(sprintf('Class "%s" were not expected to be instantiated!', $className));
            }
            return '';
        });

        $eventStoreManager->getEventStore('eventStore2');
    }

    public function getEventStoreForStreamNameDataProvider(): array
    {
        return [
            ['streamName' => 'Inactive', 'expectedEventStore' => 'fallbackStore'],
            ['streamName' => 'NoMatch', 'expectedEventStore' => 'fallbackStore'],
            ['streamName' => 'NoMatch:Foo', 'expectedEventStore' => 'fallbackStore'],
            ['streamName' => 'Bounded.Context1:Foo', 'expectedEventStore' => 'fallbackStore'],
            ['streamName' => 'Bounded.Context234:Foo', 'expectedEventStore' => 'fallbackStore'],
            ['streamName' => 'Bounded.Context2', 'expectedEventStore' => 'eventStore2'],
            ['streamName' => 'Bounded.Context2:Foo', 'expectedEventStore' => 'eventStore2'],
            ['streamName' => 'Bounded.Context23', 'expectedEventStore' => 'eventStore3'],
            ['streamName' => 'Bounded.Context23:Foo', 'expectedEventStore' => 'eventStore3'],
            ['streamName' => 'Bounded.Context2.Sub', 'expectedEventStore' => 'eventStore3'],
            ['streamName' => 'Bounded.Context2.Sub:Foo', 'expectedEventStore' => 'eventStore3'],
            ['streamName' => 'Bounded.Context3:Xyz', 'expectedEventStore' => 'eventStore3'],
        ];
    }

    /**
     * @param string $streamName
     * @param string $expectedEventStore
     * @test
     * @dataProvider getEventStoreForStreamNameDataProvider
     */
    public function getEventStoreForStreamNameTests(string $streamName, string $expectedEventStore): void
    {
        $this->mockObjectManager->expects(self::atLeastOnce())->method('get')->willReturnCallback(function ($className) use ($expectedEventStore) {
            $storageClassName = $expectedEventStore . 'Storage';
            switch ($className) {
                case EventStore::class:
                    return $this->mockEventStore;
                case $storageClassName;
                    return $this->mockEventStorage;
                default:
                    $this->fail(sprintf('Class "%s" were not expected to be instantiated, expected "%s" or "%s"', $className, $storageClassName, EventStore::class));
            }
            return '';
        });

        $this->eventStoreManager->getEventStoreForStreamName(StreamName::fromString($streamName));
    }

    /**
     * @test
     */
    public function getEventStoreForEventListenerReturnsFallbackEventStoreIfAnEmptyStringIsGiven(): void
    {
        $this->mockObjectManager->expects(self::at(0))->method('getPackageKeyByObjectName')->with('')->willReturn(false);
        $this->mockObjectManager->expects(self::at(1))->method('get')->with('fallbackStoreStorage')->willReturn($this->mockEventStorage);
        $this->mockObjectManager->expects(self::at(2))->method('get')->with(EventStore::class, $this->mockEventStorage)->willReturn($this->mockEventStore);
        $eventStore = $this->eventStoreManager->getEventStoreForEventListener('');
        self::assertSame($this->mockEventStore, $eventStore);
    }

    public function getEventStoreForEventListenerDataProvider(): array
    {
        return [
            ['resolvedPackageKey' => '', 'expectedEventStore' => 'fallbackStore'],
            ['resolvedPackageKey' => 'NoMatch', 'expectedEventStore' => 'fallbackStore'],
            ['resolvedPackageKey' => 'Inactive', 'expectedEventStore' => 'fallbackStore'],
            ['resolvedPackageKey' => 'Bounded.Context1', 'expectedEventStore' => 'fallbackStore'],
            ['resolvedPackageKey' => 'Bounded.Context234', 'expectedEventStore' => 'fallbackStore'],
            ['resolvedPackageKey' => 'Bounded.Context2', 'expectedEventStore' => 'eventStore2'],
            ['resolvedPackageKey' => 'Bounded.Context23', 'expectedEventStore' => 'eventStore3'],
            ['resolvedPackageKey' => 'Bounded.Context2.Sub', 'expectedEventStore' => 'eventStore3'],
            ['resolvedPackageKey' => 'Bounded.Context3', 'expectedEventStore' => 'eventStore3'],
        ];
    }

    /**
     * @param string $resolvedPackageKey
     * @param string $expectedEventStore
     * @test
     * @dataProvider getEventStoreForEventListenerDataProvider
     */
    public function getEventStoreForEventListenerTests(string $resolvedPackageKey, string $expectedEventStore): void
    {
        $this->mockObjectManager->expects(self::at(0))->method('getPackageKeyByObjectName')->with('listenerClassName')->willReturn($resolvedPackageKey);
        $this->mockObjectManager->expects(self::atLeastOnce())->method('get')->willReturnCallback(function ($className) use ($expectedEventStore) {
            $storageClassName = $expectedEventStore . 'Storage';
            switch ($className) {
                case EventStore::class:
                    return $this->mockEventStore;
                case $storageClassName;
                    return $this->mockEventStorage;
                default:
                    $this->fail(sprintf('Class "%s" were not expected to be instantiated, expected "%s" or "%s"', $className, $storageClassName, EventStore::class));
            }
            return '';
        });

        $this->eventStoreManager->getEventStoreForEventListener('listenerClassName');
    }

    public function getEventStoreForBoundedContextDataProvider(): array
    {
        return [
            ['boundedContext' => '', 'expectedEventStore' => 'fallbackStore'],
            ['boundedContext' => 'NoMatch', 'expectedEventStore' => 'fallbackStore'],
            ['boundedContext' => 'Inactive', 'expectedEventStore' => 'fallbackStore'],
            ['boundedContext' => 'NoMatch:Foo', 'expectedEventStore' => 'fallbackStore'],
            ['boundedContext' => 'Bounded.Context1', 'expectedEventStore' => 'fallbackStore'],
            ['boundedContext' => 'Bounded.Context234', 'expectedEventStore' => 'fallbackStore'],
            ['boundedContext' => 'Bounded.Context2', 'expectedEventStore' => 'eventStore2'],
            ['boundedContext' => 'Bounded.Context23', 'expectedEventStore' => 'eventStore3'],
            ['boundedContext' => 'Bounded.Context2.Sub', 'expectedEventStore' => 'eventStore3'],
            ['boundedContext' => 'Bounded.Context3', 'expectedEventStore' => 'eventStore3'],
        ];
    }

    /**
     * @param string $boundedContext
     * @param string $expectedEventStore
     * @test
     * @dataProvider getEventStoreForBoundedContextDataProvider
     */
    public function getEventStoreForBoundedContextTests(string $boundedContext, string $expectedEventStore): void
    {
        $this->mockObjectManager->expects(self::atLeastOnce())->method('get')->willReturnCallback(function ($className) use ($expectedEventStore) {
            $storageClassName = $expectedEventStore . 'Storage';
            switch ($className) {
                case EventStore::class:
                    return $this->mockEventStore;
                case $storageClassName;
                    return $this->mockEventStorage;
                default:
                    $this->fail(sprintf('Class "%s" were not expected to be instantiated, expected "%s" or "%s"', $className, $storageClassName, EventStore::class));
            }
            return '';
        });

        $this->eventStoreManager->getEventStoreForBoundedContext($boundedContext);
    }

    /**
     * @test
     */
    public function getAllEventStoresThrowsExceptionIfNoEventStoreIsConfigured(): void
    {
        $this->expectException(StorageConfigurationException::class);
        $mockConfiguration = [];
        $eventStoreManager = new EventStoreManager($this->mockObjectManager, $mockConfiguration);
        $eventStoreManager->getAllEventStores();
    }

    /**
     * @test
     */
    public function getAllEventStoresInstantiatesAndReturnsAllConfiguredEventStores(): void
    {
        $mockEventStores = [
            'fallbackStore' => new EventStore($this->mockEventStorage),
            'eventStore2' => new EventStore($this->mockEventStorage),
            'eventStore3' => new EventStore($this->mockEventStorage),
        ];

        $this->mockObjectManager->expects(self::at(0))->method('get')->with('fallbackStoreStorage')->willReturn($this->mockEventStorage);
        $this->mockObjectManager->expects(self::at(1))->method('get')->with(EventStore::class)->willReturn($mockEventStores['fallbackStore']);
        $this->mockObjectManager->expects(self::at(2))->method('get')->with('eventStore2Storage')->willReturn($this->mockEventStorage);
        $this->mockObjectManager->expects(self::at(3))->method('get')->with(EventStore::class)->willReturn($mockEventStores['eventStore2']);
        $this->mockObjectManager->expects(self::at(4))->method('get')->with('eventStore3Storage')->willReturn($this->mockEventStorage);
        $this->mockObjectManager->expects(self::at(5))->method('get')->with(EventStore::class)->willReturn($mockEventStores['eventStore3']);

        $actualResult = $this->eventStoreManager->getAllEventStores();
        self::assertSame($mockEventStores, $actualResult);
    }
}
