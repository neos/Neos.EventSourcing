<?php
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
use Neos\EventSourcing\EventListener\Exception\LastAppliedEventIdCantBeReservedException;
use Neos\Flow\Annotations as Flow;

/**
 * A generic Doctrine-based repository for applied events logs.
 *
 * This repository can be used by projectors, process managers or other asynchronous event listeners for keeping
 * track of the highest sequence number of the applied events. This information is used and updated when catching up
 * on new events.
 *
 * Alternatively to using this repository, event listeners are free to implement their own way of storing this
 * information.
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

    /**
     * Returns the last seen sequence number of events which has been applied to the concrete event listener.
     *
     * @param string $eventListenerIdentifier
     * @return string|null
     */
    public function reserveLastAppliedEventId(string $eventListenerIdentifier): ?string
    {
        try {
            $lastAppliedEventId = $this->fetchHighestAppliedEventId($eventListenerIdentifier);
        } catch (LastAppliedEventIdCantBeReservedException $exception) {
            try {
                $this->dbal->executeUpdate('INSERT INTO ' . $this->dbal->quoteIdentifier(self::TABLE_NAME) . ' (eventlisteneridentifier) VALUES (:eventListenerIdentifier)', [
                    'eventListenerIdentifier' => $eventListenerIdentifier
                ]);
                $this->dbal->commit();
            } catch (DBALException $exception) {
                throw new \RuntimeException($exception->getMessage(), 1544207944, $exception);
            }
            return $this->reserveLastAppliedEventId($eventListenerIdentifier);
        }
        return $lastAppliedEventId;
    }

    private function fetchHighestAppliedEventId(string $eventListenerIdentifier): ?string
    {
        try {
            // TODO longer/configurable timeout?
            $this->dbal->executeQuery('SET innodb_lock_wait_timeout = 1');
        } catch (DBALException $exception) {
            throw new \RuntimeException($exception->getMessage(), 1544207612, $exception);
        }
        $this->dbal->beginTransaction();
        try {
            $lastAppliedEventId = $this->dbal->fetchColumn('
                SELECT lastappliedeventid
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
            throw new LastAppliedEventIdCantBeReservedException(sprintf('Could not reserve last applied event id for listener "%s"', $eventListenerIdentifier), 1523456892, $exception);
        } catch (DBALException $exception) {
            throw new \RuntimeException($exception->getMessage(), 1544207778, $exception);
        }
        if ($lastAppliedEventId === false) {
            throw new LastAppliedEventIdCantBeReservedException(sprintf('Could not reserve last applied event id for listener "%s"', $eventListenerIdentifier), 1541002644);
        }
        return $lastAppliedEventId;
    }

    public function releaseLastAppliedEventId(): void
    {
        try {
            $this->dbal->commit();
        } catch (ConnectionException $exception) {
        }
    }

    /**
     * Saves the $sequenceNumber as the last seen sequence number of events which have been applied to the concrete
     * event listener.
     *
     * @param string $eventListenerIdentifier
     * @param string $eventId
     * @return void
     */
    public function saveLastAppliedEventId(string $eventListenerIdentifier, string $eventId): void
    {
        // TODO: Fails if no matching entry exists
        try {
            $this->dbal->update(
                self::TABLE_NAME,
                ['lastappliedeventid' => $eventId],
                ['eventlisteneridentifier' => $eventListenerIdentifier]
            );
        } catch (DBALException $exception) {
            throw new \RuntimeException(sprintf('Could not save last applied event id for listener "%s"', $eventListenerIdentifier), 1544207099, $exception);
        }
        try {
            $this->dbal->commit();
        } catch (ConnectionException $exception) {
            // TODO handle exception
        }
    }
}
