<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Tests\Functional\EventListener\AppliedEventsStorage;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Neos\EventSourcing\EventListener\AppliedEventsStorage\DoctrineAppliedEventsStorage;
use Neos\EventSourcing\EventListener\Exception\HighestAppliedSequenceNumberCantBeReservedException;
use Neos\Flow\Tests\FunctionalTestCase;

class DoctrineAdapterTest extends FunctionalTestCase
{

    protected static $testablePersistenceEnabled = true;

    /**
     * @var DoctrineAppliedEventsStorage
     */
    private $adapter1;

    /**
     * @var DoctrineAppliedEventsStorage
     */
    private $adapter2;

    public function setUp(): void
    {
        parent::setUp();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->objectManager->get(EntityManagerInterface::class);
        $dbal1 = $entityManager->getConnection();

        $platform = $dbal1->getDatabasePlatform()->getName();
        if ($platform !== 'mysql' && $platform !== 'postgresql') {
            self::markTestSkipped(sprintf('DB platform "%s" is not supported', $platform));
        }

        $this->adapter1 = new DoctrineAppliedEventsStorage($dbal1);

        $dbal2 = DriverManager::getConnection($dbal1->getParams(), new Configuration());
        $this->adapter2 = new DoctrineAppliedEventsStorage($dbal2);
    }

    public function tearDown(): void
    {
        $this->adapter1->releaseHighestAppliedSequenceNumber();
        $this->adapter2->releaseHighestAppliedSequenceNumber();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function reserveHighestAppliedEventSequenceNumberFailsIfEventListenerIsNotInitialized(): void
    {
        $this->expectException(HighestAppliedSequenceNumberCantBeReservedException::class);
        $this->expectExceptionCode(1550948433);
        $this->adapter1->reserveHighestAppliedEventSequenceNumber('someEventListenerIdentifier');
    }

    /**
     * @test
     */
    public function reserveHighestAppliedEventSequenceNumberFailsIfSequenceNumberIsReserved(): void
    {
        $this->adapter1->initializeHighestAppliedSequenceNumber('someEventListenerIdentifier');
        $this->adapter1->reserveHighestAppliedEventSequenceNumber('someEventListenerIdentifier');

        $this->expectException(HighestAppliedSequenceNumberCantBeReservedException::class);
        $this->expectExceptionCode(1523456892);
        $this->adapter2->reserveHighestAppliedEventSequenceNumber('someEventListenerIdentifier');
    }

    /**
     * @test
     */
    public function reserveHighestAppliedEventSequenceNumberFailsWithin3Seconds(): void
    {
        $this->adapter1->initializeHighestAppliedSequenceNumber('someEventListenerIdentifier');
        $this->adapter1->reserveHighestAppliedEventSequenceNumber('someEventListenerIdentifier');

        $startTime = microtime(true);
        $timeDelta = null;
        try {
            $this->adapter2->reserveHighestAppliedEventSequenceNumber('someEventListenerIdentifier');
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
        $this->adapter1->initializeHighestAppliedSequenceNumber('someEventListenerIdentifier');
        $this->adapter1->reserveHighestAppliedEventSequenceNumber('someEventListenerIdentifier');
        $this->adapter1->saveHighestAppliedSequenceNumber('someEventListenerIdentifier', 42);
        $this->adapter1->releaseHighestAppliedSequenceNumber();

        self::assertSame(42, $this->adapter2->reserveHighestAppliedEventSequenceNumber('someEventListenerIdentifier'));
    }

}
