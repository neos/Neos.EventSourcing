<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Functional\EventListener;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Neos\EventSourcing\EventListener\AppliedEventsLogRepository;
use Neos\EventSourcing\EventListener\Exception\HighestAppliedSequenceNumberCantBeReservedException;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Utility\ObjectAccess;

class AppliedEventsLogRepositoryTest extends FunctionalTestCase
{

    protected static $testablePersistenceEnabled = true;

    /**
     * @var AppliedEventsLogRepository
     */
    private $repository1;

    /**
     * @var AppliedEventsLogRepository
     */
    private $repository2;

    public function setUp(): void
    {
        parent::setUp();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->objectManager->get(EntityManagerInterface::class);
        $this->repository1 = new AppliedEventsLogRepository($entityManager);

        $this->repository2 = new AppliedEventsLogRepository($entityManager);
        $connection = DriverManager::getConnection($entityManager->getConnection()->getParams(), new Configuration());
        ObjectAccess::setProperty($this->repository2, 'dbal', $connection, true);
    }

    public function tearDown(): void
    {
        $this->repository1->releaseHighestAppliedSequenceNumber();
        $this->repository2->releaseHighestAppliedSequenceNumber();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function reserveHighestAppliedEventSequenceNumberFailsIfEventListenerIsNotInitialized(): void
    {
        $this->expectException(HighestAppliedSequenceNumberCantBeReservedException::class);
        $this->expectExceptionCode(1550948433);
        $this->repository1->reserveHighestAppliedEventSequenceNumber('someEventListenerIdentifier');
    }

    /**
     * @test
     */
    public function reserveHighestAppliedEventSequenceNumberFailsIfSequenceNumberIsReserved(): void
    {
        $this->repository1->initializeHighestAppliedSequenceNumber('someEventListenerIdentifier');
        $this->repository1->reserveHighestAppliedEventSequenceNumber('someEventListenerIdentifier');

        $this->expectException(HighestAppliedSequenceNumberCantBeReservedException::class);
        $this->expectExceptionCode(1523456892);
        $this->repository2->reserveHighestAppliedEventSequenceNumber('someEventListenerIdentifier');
    }

    /**
     * @test
     */
    public function reserveHighestAppliedEventSequenceNumberFailsWithin3Seconds(): void
    {
        $this->repository1->initializeHighestAppliedSequenceNumber('someEventListenerIdentifier');
        $this->repository1->reserveHighestAppliedEventSequenceNumber('someEventListenerIdentifier');

        $startTime = microtime(true);
        $timeDelta = null;
        try {
            $this->repository2->reserveHighestAppliedEventSequenceNumber('someEventListenerIdentifier');
        } catch (HighestAppliedSequenceNumberCantBeReservedException $exception) {
            $timeDelta = microtime(true) - $startTime;
        }
        if ($timeDelta === null) {
            self::fail('HighestAppliedSequenceNumberCantBeReservedException was not thrown!');
        }
        self::assertLessThan(3, $timeDelta);
    }

    /**
     * @test
     */
    public function saveHighestAppliedSequenceNumberAllowsToSetSequenceNumber(): void
    {
        $this->repository1->initializeHighestAppliedSequenceNumber('someEventListenerIdentifier');
        $this->repository1->reserveHighestAppliedEventSequenceNumber('someEventListenerIdentifier');
        $this->repository1->saveHighestAppliedSequenceNumber('someEventListenerIdentifier', 42);
        $this->repository1->releaseHighestAppliedSequenceNumber();

        self::assertSame(42, $this->repository2->reserveHighestAppliedEventSequenceNumber('someEventListenerIdentifier'));
    }

}
