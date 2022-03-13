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
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::$bootstrap->getObjectManager()->get(EntityManagerInterface::class);
        $dbal1 = $entityManager->getConnection();

        $platform = $dbal1->getDatabasePlatform()->getName();
        if ($platform !== 'mysql' && $platform !== 'postgresql') {
            self::markTestSkipped(sprintf('DB platform "%s" is not supported', $platform));
        }
        parent::setUp();

        $this->adapter1 = new DoctrineAppliedEventsStorage($dbal1, 'someEventListenerIdentifier');

        $dbal2 = DriverManager::getConnection($dbal1->getParams(), new Configuration());
        $this->adapter2 = new DoctrineAppliedEventsStorage($dbal2, 'someEventListenerIdentifier');
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
    public function reserveHighestAppliedEventSequenceNumberFailsIfSequenceNumberIsReserved(): void
    {
        $this->adapter1->reserveHighestAppliedEventSequenceNumber();

        $this->expectException(HighestAppliedSequenceNumberCantBeReservedException::class);
        $this->expectExceptionCode(1523456892);
        $this->adapter2->reserveHighestAppliedEventSequenceNumber();
    }

    /**
     * @test
     */
    public function reserveHighestAppliedEventSequenceNumberFailsWithin3Seconds(): void
    {
        $this->adapter1->reserveHighestAppliedEventSequenceNumber();

        $startTime = microtime(true);
        $timeDelta = null;
        try {
            $this->adapter2->reserveHighestAppliedEventSequenceNumber();
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
        $this->adapter1->reserveHighestAppliedEventSequenceNumber();
        $this->adapter1->saveHighestAppliedSequenceNumber(42);
        $this->adapter1->releaseHighestAppliedSequenceNumber();

        self::assertSame(42, $this->adapter2->reserveHighestAppliedEventSequenceNumber());
    }

}
