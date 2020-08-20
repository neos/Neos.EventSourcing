<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventListener\AppliedEventsStorage;

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
use Neos\EventSourcing\EventListener\Exception\HighestAppliedSequenceNumberCantBeReservedException;

/**
 * Doctrine DBAL adapter for the AppliedEventsStorageInterface
 */
final class DoctrineAppliedEventsStorage implements AppliedEventsStorageInterface
{
    /**
     * @var Connection
     */
    private $dbal;

    /**
     * @var string
     */
    private $eventListenerIdentifier;

    public function __construct(Connection $dbal, string $eventListenerIdentifier)
    {
        $this->dbal = $dbal;
        $this->eventListenerIdentifier = $eventListenerIdentifier;
        $this->initializeHighestAppliedSequenceNumber();
    }

    private function initializeHighestAppliedSequenceNumber(): void
    {
        if ($this->dbal->getTransactionNestingLevel() !== 0) {
            throw new \RuntimeException('initializeHighestAppliedSequenceNumber only works not inside a transaction.', 1551005203);
        }

        try {
            $this->dbal->executeUpdate(
                'INSERT INTO ' . AppliedEventsLog::TABLE_NAME . ' (eventListenerIdentifier, highestAppliedSequenceNumber) VALUES (:eventListenerIdentifier, -1)',
                ['eventListenerIdentifier' => $this->eventListenerIdentifier]
            );
        } catch (DBALException $exception) {
            // UniqueConstraintViolationException = The sequence number is already registered => ignore
            if ($exception instanceof UniqueConstraintViolationException) {
                return;
            }
            throw new \RuntimeException(sprintf('Failed to initialize highest sequence number for "%s": %s', $this->eventListenerIdentifier, $exception->getMessage()), 1567081020, $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function reserveHighestAppliedEventSequenceNumber(): int
    {
        if ($this->dbal->getTransactionNestingLevel() !== 0) {
            throw new \RuntimeException('A transaction is active already, can\'t fetch highestAppliedSequenceNumber!', 1550865301);
        }

        $this->dbal->beginTransaction();
        $this->setLockTimeout();
        try {
            $highestAppliedSequenceNumber = $this->dbal->fetchColumn(
                '
                SELECT highestAppliedSequenceNumber
                FROM ' . $this->dbal->quoteIdentifier(AppliedEventsLog::TABLE_NAME) . '
                WHERE eventlisteneridentifier = :eventListenerIdentifier '
                . $this->dbal->getDatabasePlatform()->getForUpdateSQL(),
                ['eventListenerIdentifier' => $this->eventListenerIdentifier]
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
            throw new HighestAppliedSequenceNumberCantBeReservedException(sprintf('Could not reserve highest applied sequence number for listener "%s"', $this->eventListenerIdentifier), 1523456892, $exception);
        } catch (DBALException $exception) {
            throw new \RuntimeException($exception->getMessage(), 1544207778, $exception);
        }
        if ($highestAppliedSequenceNumber === false) {
            throw new HighestAppliedSequenceNumberCantBeReservedException(sprintf('Could not reserve highest applied sequence number for listener "%s", because the corresponding row was not found in the %s table. This means the method initializeHighestAppliedSequenceNumber() was not called beforehand.', $this->eventListenerIdentifier, AppliedEventsLog::TABLE_NAME), 1550948433);
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

    /**
     * @inheritDoc
     */
    public function releaseHighestAppliedSequenceNumber(): void
    {
        try {
            $this->dbal->commit();
        } catch (ConnectionException $e) {
        }
    }

    /**
     * @inheritDoc
     */
    public function saveHighestAppliedSequenceNumber(int $sequenceNumber): void
    {
        // Fails if no matching entry exists; which is fine because initializeHighestAppliedSequenceNumber() must be called beforehand.
        try {
            $this->dbal->update(
                AppliedEventsLog::TABLE_NAME,
                ['highestAppliedSequenceNumber' => $sequenceNumber],
                ['eventListenerIdentifier' => $this->eventListenerIdentifier]
            );
        } catch (DBALException $exception) {
            throw new \RuntimeException(sprintf('Could not save highest applied sequence number for listener "%s". Did you call initializeHighestAppliedSequenceNumber() beforehand?', $this->eventListenerIdentifier), 1544207099, $exception);
        }
    }
}
