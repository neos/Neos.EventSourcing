<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventListener;
/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager as DoctrineObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Neos\EventSourcing\EventListener\Exception\HighestAppliedSequenceNumberCantBeReservedException;
use Neos\Flow\Annotations as Flow;

/**
 * TODO Document
 *
 * @api
 * @Flow\Scope("singleton")
 */
class AppliedEventsLogRepository
{
    const TABLE_NAME = 'neos_eventsourcing_eventlistener_appliedeventslog';

    /**
     * @var Connection
     */
    private $dbal;

    /**
     * @param DoctrineObjectManager $entityManager
     *
     *
     * // TODO use EventStore DBAL connection (?)
     */
    public function __construct(DoctrineObjectManager $entityManager)
    {
        if (!$entityManager instanceof DoctrineEntityManager) {
            throw new \RuntimeException(sprintf('The injected entityManager is expected to be an instance of "%s". Given: "%s"', DoctrineEntityManager::class, get_class($entityManager)), 1521556748);
        }
        $this->dbal = $entityManager->getConnection();
    }

    public function reserveHighestAppliedEventSequenceNumber(string $eventListenerIdentifier): int
    {
        try {
            $sequenceNumber = $this->fetchHighestAppliedSequenceNumber($eventListenerIdentifier);
        } catch (HighestAppliedSequenceNumberCantBeReservedException $exception) {
            try {
                $this->dbal->executeUpdate('INSERT INTO ' . $this->dbal->quoteIdentifier(self::TABLE_NAME) . ' (eventListenerIdentifier, highestAppliedSequenceNumber) VALUES (:eventListenerIdentifier, -1)', [
                    'eventListenerIdentifier' => $eventListenerIdentifier
                ]);
                $this->dbal->commit();
            } catch (DBALException $exception) {
                throw new \RuntimeException($exception->getMessage(), 1544207944, $exception);
            }
            return $this->reserveHighestAppliedEventSequenceNumber($eventListenerIdentifier);
        }
        return (int)$sequenceNumber;
    }

    private function fetchHighestAppliedSequenceNumber(string $eventListenerIdentifier): ?int
    {
        try {
            // TODO longer/configurable timeout?
            $this->dbal->executeQuery('SET innodb_lock_wait_timeout = 1');
        } catch (DBALException $exception) {
            throw new \RuntimeException($exception->getMessage(), 1544207612, $exception);
        }
        $this->dbal->beginTransaction();
        try {
            $highestAppliedSequenceNumber = $this->dbal->fetchColumn('
                SELECT highestAppliedSequenceNumber
                FROM ' . $this->dbal->quoteIdentifier(self::TABLE_NAME) . '
                WHERE eventlisteneridentifier = :eventListenerIdentifier ' . $this->dbal->getDatabasePlatform()->getForUpdateSQL(),
                ['eventListenerIdentifier' => $eventListenerIdentifier]
            );
        } catch (DriverException $exception) {
            try {
                $this->dbal->rollBack();
            } catch (ConnectionException $exception) {
            }
            // TODO 1205 = ER_LOCK_WAIT_TIMEOUT is MySQL Specific (https://dev.mysql.com/doc/refman/8.0/en/server-error-reference.html#error_er_lock_wait_timeout)
            if ($exception->getErrorCode() !== 1205) {
                throw new \RuntimeException($exception->getMessage(), 1544207633, $exception);
            }
            throw new HighestAppliedSequenceNumberCantBeReservedException(sprintf('Could not reserve highest applied sequence number for listener "%s"', $eventListenerIdentifier), 1523456892, $exception);
        } catch (DBALException $exception) {
            throw new \RuntimeException($exception->getMessage(), 1544207778, $exception);
        }
        if ($highestAppliedSequenceNumber === false) {
            throw new HighestAppliedSequenceNumberCantBeReservedException(sprintf('Could not reserve highest applied sequence number for listener "%s"', $eventListenerIdentifier), 1541002644);
        }
        return (int)$highestAppliedSequenceNumber;
    }

    public function releaseHighestAppliedSequenceNumber(): void
    {
        try {
            $this->dbal->commit();
        } catch (ConnectionException $exception) {
        }
    }

    public function saveHighestAppliedSequenceNumber(string $eventListenerIdentifier, int $sequenceNumber): void
    {
        // TODO: Fails if no matching entry exists
        try {
            $this->dbal->update(
                self::TABLE_NAME,
                ['highestAppliedSequenceNumber' => $sequenceNumber],
                ['eventListenerIdentifier' => $eventListenerIdentifier]
            );
        } catch (DBALException $exception) {
            throw new \RuntimeException(sprintf('Could not save highest applied sequence number for listener "%s"', $eventListenerIdentifier), 1544207099, $exception);
        }
//        try {
//            $this->dbal->commit();
//        } catch (ConnectionException $exception) {
//            // TODO handle exception
//        }
    }

    /**
     *
     * @param string $eventListenerIdentifier
     * @return void
     */
    public function removeHighestAppliedSequenceNumber(string $eventListenerIdentifier): void
    {
        // TODO: Fails if no matching entry exists
        try {
            $this->dbal->update(
                self::TABLE_NAME,
                ['highestAppliedSequenceNumber' => -1],
                ['eventListenerIdentifier' => $eventListenerIdentifier]
            );
        } catch (DBALException $exception) {
            throw new \RuntimeException(sprintf('Could not reset highest applied sequence number for listener "%s"', $eventListenerIdentifier), 1544213138, $exception);
        }
    }
}
