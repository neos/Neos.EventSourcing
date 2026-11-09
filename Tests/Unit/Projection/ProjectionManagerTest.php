<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\Projection;

use DG\BypassFinals;
use Neos\EventSourcing\EventListener\EventListenerInvoker;
use Neos\EventSourcing\EventListener\Mapping\DefaultEventToListenerMappingProvider;
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\EventSourcing\Projection\ProjectionManager;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ProjectionManagerTest extends UnitTestCase
{
    /**
     * @var MockObject | ObjectManagerInterface
     */
    private $mockObjectManager;

    /**
     * @var MockObject | ReflectionService
     */
    private $mockReflectionService;

    /**
     * @var MockObject | EventStoreFactory
     */
    private $mockEventStoreFactory;

    /**
     * @var MockObject | DefaultEventToListenerMappingProvider
     */
    private $mockMappingProvider;

    /**
     * @var ProjectionManager
     */
    private $projectionManager;

    /**
     * @var array
     */
    private $projectorIdentifiers;
    /**
     * @var array
     */
    private $projectorClassNames;

    /**
     * @return void
     * @throws
     * @noinspection ClassMockingCorrectnessInspection
     */
    public function setUp(): void
    {
        $this->markTestSkipped('We need to fix these tests (to not rely on dg/bypass-finals');

        $this->mockReflectionService = $this->createMock(ReflectionService::class);
        $md5 = md5((string)time());
        $this->projectorIdentifiers = [
            'acme.somepackage:acmesomepackagetest' . $md5 . '0',
            'acme.somepackage:acmesomepackagetest' . $md5 . '1'
        ];

        $this->projectorClassNames = [
            $this->projectorIdentifiers[0] => $this->getMockClass(ProjectorInterface::class, [], [], 'AcmeSomePackageTest' . $md5 . '0Projector'),
            $this->projectorIdentifiers[1] => $this->getMockClass(ProjectorInterface::class, [], [], 'AcmeSomePackageTest' . $md5 . '1Projector'),
        ];

        $this->mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(ProjectorInterface::class)->willReturn([
            $this->projectorClassNames[$this->projectorIdentifiers[0]],
            $this->projectorClassNames[$this->projectorIdentifiers[1]]
        ]);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);
        $this->mockObjectManager->method('getPackageKeyByObjectName')->willReturn('Acme.SomePackage');

        $this->mockEventStoreFactory = $this->createMock(EventStoreFactory::class);
        $this->mockMappingProvider = $this->createMock(DefaultEventToListenerMappingProvider::class);

        $this->projectionManager = new ProjectionManager($this->mockObjectManager, $this->mockEventStoreFactory, $this->mockMappingProvider);
    }

    /**
     * @test
     * @throws
     */
    public function getProjectionsReturnsDetectedProjections(): void
    {
        $this->projectionManager->initializeObject();

        $projections = $this->projectionManager->getProjections();
        $this->assertSame($this->projectorClassNames[$this->projectorIdentifiers[0]], $projections[0]->getProjectorClassName());
        $this->assertSame($this->projectorClassNames[$this->projectorIdentifiers[1]], $projections[1]->getProjectorClassName());
    }

    /**
     * @test
     * @noinspection ClassMockingCorrectnessInspection
     */
    public function catchUpCallsEventListenerInvokerForCatchingUp(): void
    {
        $mockEventListenerInvoker = $this->createMock(EventListenerInvoker::class);

        $projectionManager = $this->createPartialMock(ProjectionManager::class, ['createEventListenerInvokerForProjection']);
        $projectionManager->method('createEventListenerInvokerForProjection')->with($this->projectorIdentifiers[0])->willReturn($mockEventListenerInvoker);

        $mockEventListenerInvoker->expects($this->once())->method('catchUp');
        $projectionManager->catchUp($this->projectorIdentifiers[0]);
    }

    /**
     * @test
     * @noinspection ClassMockingCorrectnessInspection
     */
    public function catchUpSetsCallbackOnEventListenerInvoker(): void
    {
        $mockEventListenerInvoker = $this->createMock(EventListenerInvoker::class);

        $projectionManager = $this->createPartialMock(ProjectionManager::class, ['createEventListenerInvokerForProjection']);
        $projectionManager->method('createEventListenerInvokerForProjection')->with($this->projectorIdentifiers[0])->willReturn($mockEventListenerInvoker);

        $callback = static function() { echo 'hello'; };

        $mockEventListenerInvoker->expects($this->once())->method('onProgress')->with($callback);
        $projectionManager->catchUp($this->projectorIdentifiers[0], $callback);
    }

    /**
     * @test
     * @noinspection ClassMockingCorrectnessInspection
     */
    public function catchUpUntilSequenceNumberCallsEventListenerInvokerForCatchingUp(): void
    {
        $mockEventListenerInvoker = $this->createMock(EventListenerInvoker::class);

        $projectionManager = $this->createPartialMock(ProjectionManager::class, ['createEventListenerInvokerForProjection']);
        $projectionManager->method('createEventListenerInvokerForProjection')->with($this->projectorIdentifiers[0])->willReturn($mockEventListenerInvoker);

        $mockEventListenerInvoker->expects($this->once())->method('withMaximumSequenceNumber')->with(42)->willReturn($mockEventListenerInvoker);
        $mockEventListenerInvoker->expects($this->once())->method('catchUp');
        $projectionManager->catchUpUntilSequenceNumber($this->projectorIdentifiers[0], 42);
    }

    /**
     * @test
     * @noinspection ClassMockingCorrectnessInspection
     */
    public function catchUpUntilSequenceNumberSetCallbackOnEventListenerInvoker(): void
    {
        $mockEventListenerInvoker = $this->createMock(EventListenerInvoker::class);

        $projectionManager = $this->createPartialMock(ProjectionManager::class, ['createEventListenerInvokerForProjection']);
        $projectionManager->method('createEventListenerInvokerForProjection')->with($this->projectorIdentifiers[0])->willReturn($mockEventListenerInvoker);

        $callback = static function() { echo 'hello'; };

        $mockEventListenerInvoker->method('withMaximumSequenceNumber')->willReturn($mockEventListenerInvoker);
        $mockEventListenerInvoker->expects($this->once())->method('onProgress')->with($callback);
        $projectionManager->catchUpUntilSequenceNumber($this->projectorIdentifiers[0], 100, $callback);
    }
}
