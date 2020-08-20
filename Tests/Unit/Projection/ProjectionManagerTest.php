<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Unit\Projection;

use DG\BypassFinals;
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
    private $projectorClassNames;

    /**
     * @return void
     * @throws
     * @noinspection ClassMockingCorrectnessInspection
     */
    public function setUp(): void
    {
        BypassFinals::enable();

        $this->mockReflectionService = $this->createMock(ReflectionService::class);
        $this->projectorClassNames = [
            'acme.somepackage:projection1' => $this->getMockClass(ProjectorInterface::class, [], [], 'TestAcme' . md5((string)time()) . '1Projector'),
            'acme.somepackage:projection2' => $this->getMockClass(ProjectorInterface::class, [], [], 'TestAcme' . md5((string)time()) . '2Projector'),
        ];

        $this->mockReflectionService->method('getAllImplementationClassNamesForInterface')->with(...ProjectorInterface::class)->willReturn([
            $this->projectorClassNames['acme.somepackage:projection1'],
            $this->projectorClassNames['acme.somepackage:projection2']
        ]);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->method('get')->with(...ReflectionService::class)->willReturn($this->mockReflectionService);
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
        $this->assertSame($this->projectorClassNames['acme.somepackage:projection1'], $projections[0]->getProjectorClassName());
        $this->assertSame($this->projectorClassNames['acme.somepackage:projection2'], $projections[1]->getProjectorClassName());
    }
}
