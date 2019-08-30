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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Neos\EventSourcing\EventListener\Exception\HighestAppliedSequenceNumberCantBeReservedException;
use Neos\Flow\Annotations as Flow;

/**
 * This Repository allows stores the last applied event sequence number
 *
 * @api
 * @Flow\Scope("singleton")
 */
class AppliedEventsLogRepository
{
    private const TABLE_NAME = 'neos_eventsourcing_eventlistener_appliedeventslog';

    /**
     * DBAL handle
     * Note: This field is protected so that it can be replaced (in tests)
     *
     * @var Connection
     */
    protected $dbal;

    /**
     * @param EntityManagerInterface $entityManager
     *
     * // TODO use EventStore DBAL connection (?)
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->dbal = $entityManager->getConnection();
    }

    /**
     * This method should be called BEFORE the Projection Workers are actually started; as otherwise,
     * inserting into the AppliedEventsLog might lead to a deadlock when multiple workers want to insert
     * the "-1" default event IDs.
     *
     * That's why this preparation is executed in the EventBus; BEFORE dispatching to the different Jobs.
     * It's a form of "HELPING", where the main (web) process helps the projection processes to have a known entry in the AppliedEventsLog.
     *
     * @param string $eventListenerIdentifier
     */
    public function initializeHighestAppliedSequenceNumber(string $eventListenerIdentifier): void
    {
        if ($this->dbal->getTransactionNestingLevel() !== 0) {
            throw new \RuntimeException('initializeHighestAppliedSequenceNumber only works not inside a transaction.', 1551005203);
        }

        try {
            $this->dbal->executeUpdate('INSERT INTO ' . self::TABLE_NAME . ' (eventListenerIdentifier, highestAppliedSequenceNumber) VALUES (:eventListenerIdentifier, -1)',
                ['eventListenerIdentifier' => $eventListenerIdentifier]
            );
        } catch (DBALException $exception) {
            // UniqueConstraintViolationException = The sequence number is already registered => ignore
            if ($exception instanceof UniqueConstraintViolationException) {
                return;
            }
            throw new \RuntimeException(sprintf('Failed to initialize highest sequence number for "%s": %s', $eventListenerIdentifier, $exception->getMessage()), 1567081020, $exception);
        }
    }

    public function reserveHighestAppliedEventSequenceNumber(string $eventListenerIdentifier): int
    {

        if ($this->dbal->getTransactionNestingLevel() !== 0) {
            throw new \RuntimeException('A transaction is active already, can\'t fetch highestAppliedSequenceNumber!', 1550865301);
        }

        $this->dbal->beginTransaction();
        $this->setLockTimeout();
        try {
            $highestAppliedSequenceNumber = $this->dbal->fetchColumn('
                SELECT highestAppliedSequenceNumber
                FROM ' . $this->dbal->quoteIdentifier(self::TABLE_NAME) . '
                WHERE eventlisteneridentifier = :eventListenerIdentifier '
                . $this->dbal->getDatabasePlatform()->getForUpdateSQL(),
                ['eventListenerIdentifier' => $eventListenerIdentifier]
            );
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (DriverException $exception) {
            try {
                $this->dbal->rollBack();
            } catch (ConnectionException $e) {
            }
            // Error code "1205" = ER_LOCK_WAIT_TIMEOUT in MySQL (https://dev.mysql.com/doc/refman/8.0/en/server-error-reference.html#error_er_lock_wait_timeout)
            // SQL State "55P03" = lock_not_available in PostgreSQL (https://www.postgresql.org/docs/9.4/errcodes-appendix.html)
            if ($exception->getErrorCode() !== 1205 && $exception->getSQLState() !== '55P03') {
                throw new \RuntimeException($exception->getMessage(), 1544207633, $exception);
            }
            throw new HighestAppliedSequenceNumberCantBeReservedException(sprintf('Could not reserve highest applied sequence number for listener "%s"', $eventListenerIdentifier), 1523456892, $exception);
        } catch (DBALException $exception) {
            throw new \RuntimeException($exception->getMessage(), 1544207778, $exception);
        }
        if ($highestAppliedSequenceNumber === false) {
            throw new HighestAppliedSequenceNumberCantBeReservedException(sprintf('Could not reserve highest applied sequence number for listener "%s", because the corresponding row was not found in the %s table. This means the method initializeHighestAppliedSequenceNumber() was not called beforehand.', $eventListenerIdentifier, self::TABLE_NAME), 1550948433);
        }
        return (int)$highestAppliedSequenceNumber;
    }

    private function setLockTimeout(): void
    {
        try {
            $platform = $this->dbal->getDatabasePlatform()->getName();
        } catch (DBALException $exception) {
            throw new \RuntimeException(sprintf('Failed to determine database platform: %s', $exception->getMessage()), 1567080718, $exception);
        }
        if ($platform === 'mysql') {
            $statement = 'SET innodb_lock_wait_timeout = 1';
        } elseif ($platform === 'postgresql') {
            $statement = 'SET LOCAL lock_timeout = \'1s\'';
        } else {
            return;
        }
        try {
            $this->dbal->executeQuery($statement);
        } catch (DBALException $exception) {
            throw new \RuntimeException(sprintf('Failed to set lock timeout: %s', $exception->getMessage()), 1544207612, $exception);
        }
    }

    public function releaseHighestAppliedSequenceNumber(): void
    {
        try {
            $this->dbal->commit();
        } catch (ConnectionException $e) {
        }
    }

    public function saveHighestAppliedSequenceNumber(string $eventListenerIdentifier, int $sequenceNumber): void
    {
        // Fails if no matching entry exists; which is fine because initializeHighestAppliedSequenceNumber() must be called beforehand.
        try {
            $this->dbal->update(
                self::TABLE_NAME,
                ['highestAppliedSequenceNumber' => $sequenceNumber],
                ['eventListenerIdentifier' => $eventListenerIdentifier]
            );
        } catch (DBALException $exception) {
            throw new \RuntimeException(sprintf('Could not save highest applied sequence number for listener "%s". Did you call initializeHighestAppliedSequenceNumber() beforehand?', $eventListenerIdentifier), 1544207099, $exception);
        }
    }

    /**
     *
     * @param string $eventListenerIdentifier
     * @return void
     */
    public function removeHighestAppliedSequenceNumber(string $eventListenerIdentifier): void
    {
        try {
            $this->dbal->delete(
                self::TABLE_NAME,
                ['eventListenerIdentifier' => $eventListenerIdentifier]
            );
        } catch (DBALException $exception) {
            throw new \RuntimeException(sprintf('Could not reset highest applied sequence number for listener "%s"', $eventListenerIdentifier), 1544213138, $exception);
        }
    }
}
