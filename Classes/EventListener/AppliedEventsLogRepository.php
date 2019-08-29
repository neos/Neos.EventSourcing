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
use Doctrine\ORM\EntityManagerInterface;
use Neos\EventSourcing\EventListener\Exception\HighestAppliedSequenceNumberCantBeReservedException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;

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
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ObjectManagerInterface $objectManager
     *
     * // TODO use EventStore DBAL connection (?)
     */
    public function __construct(EntityManagerInterface $entityManager, ObjectManagerInterface $objectManager)
    {
        $this->dbal = $entityManager->getConnection();
        $this->objectManager = $objectManager;
    }

    public function reserveHighestAppliedEventSequenceNumber(string $eventListenerIdentifier): int
    {
        try {
            // TODO longer/configurable timeout?
            $this->dbal->executeQuery('SET innodb_lock_wait_timeout = 1');
        } catch (DBALException $exception) {
            throw new \RuntimeException($exception->getMessage(), 1544207612, $exception);
        }

        if ($this->dbal->getTransactionNestingLevel() !== 0) {
            throw new \RuntimeException('A transaction is active already, can\'t fetch highestAppliedSequenceNumber!', 1550865301);
        }

        $this->dbal->beginTransaction();
        try {
            $highestAppliedSequenceNumber = $this->dbal->fetchColumn('
                SELECT highestAppliedSequenceNumber
                FROM ' . $this->dbal->quoteIdentifier(self::TABLE_NAME) . '
                WHERE eventlisteneridentifier = :eventListenerIdentifier '
                . $this->dbal->getDatabasePlatform()->getForUpdateSQL(),
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
            throw new HighestAppliedSequenceNumberCantBeReservedException(sprintf('Could not reserve highest applied sequence number for listener "%s", because the corresponding row was not found in the neos_eventsourcing_eventlistener_appliedeventslog table. This means the method ensureHighestAppliedSequenceNumbersAreInitialized() was not called beforehand.', $eventListenerIdentifier), 1550948433);
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
        // Fails if no matching entry exists; which is fine because ensureHighestAppliedSequenceNumbersAreInitialized() must be called beforehand.
        try {
            $this->dbal->update(
                self::TABLE_NAME,
                ['highestAppliedSequenceNumber' => $sequenceNumber],
                ['eventListenerIdentifier' => $eventListenerIdentifier]
            );
        } catch (DBALException $exception) {
            throw new \RuntimeException(sprintf('Could not save highest applied sequence number for listener "%s". Did you call ensureHighestAppliedSequenceNumbersAreInitialized() beforehand?', $eventListenerIdentifier), 1544207099, $exception);
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

    /**
     * This method should be called BEFORE the Projection Workers are actually started; as otherwise,
     * inserting into the AppliedEventsLog might lead to a deadlock when multiple workers want to insert
     * the "-1" default event IDs.
     *
     * That's why this preparation is executed in the EventBus; BEFORE dispatching to the different Jobs.
     * It's a form of "HELPING", where the main (web) process helps the projection processes to have a known entry in the AppliedEventsLog.
     */
    public function ensureHighestAppliedSequenceNumbersAreInitialized()
    {
        if ($this->dbal->getTransactionNestingLevel() !== 0) {
            throw new \RuntimeException('ensureHighestAppliedSequenceNumbersAreInitialized only works not inside a transaction.');
        }

        $this->dbal->transactional(function () {
            foreach (self::getAllEventListeners($this->objectManager) as $eventListenerIdentifier) {
                // HINT: we do a "INSERT IGNORE" here, meaning "if the primary key (eventListenerIdentifier) already exists, the insert is not done".
                // Which is exactly what we want; "only insert "-1" if no value existed yet.
                $this->dbal->executeUpdate('INSERT IGNORE INTO ' . self::TABLE_NAME . ' (eventListenerIdentifier, highestAppliedSequenceNumber) VALUES (:eventListenerIdentifier, -1)',
                    ['eventListenerIdentifier' => $eventListenerIdentifier]
                );
            }
        });
    }

    /**
     * Create mapping between Event class name and Event type
     *
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @Flow\CompileStatic
     */
    protected static function getAllEventListeners(ObjectManagerInterface $objectManager): array
    {
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);
        return $reflectionService->getAllImplementationClassNamesForInterface(EventListenerInterface::class);
    }
}
