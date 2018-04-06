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
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
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
     * @return int
     */
    public function reserveHighestAppliedSequenceNumber(string $eventListenerIdentifier): int
    {
        $this->dbal->beginTransaction();
        $highestAppliedSequenceNumber = $this->dbal->fetchColumn('
            SELECT highestappliedsequencenumber
            FROM ' . $this->dbal->quoteIdentifier(self::TABLE_NAME) . '
            WHERE eventlisteneridentifier = :eventListenerIdentifier ' . $this->dbal->getDatabasePlatform()->getForUpdateSQL(),
            ['eventListenerIdentifier' => $eventListenerIdentifier]
        );
        if ($highestAppliedSequenceNumber === false) {
            // FIXME this is not "transaction safe"
            $this->dbal->insert(
                self::TABLE_NAME,
                [
                    'eventlisteneridentifier' => $eventListenerIdentifier,
                    'highestappliedsequencenumber' => 0
                ]
            );
            $highestAppliedSequenceNumber = $this->dbal->fetchColumn('
                SELECT highestappliedsequencenumber
                FROM ' . $this->dbal->quoteIdentifier(self::TABLE_NAME) . '
                WHERE eventlisteneridentifier = :eventListenerIdentifier ' . $this->dbal->getDatabasePlatform()->getForUpdateSQL(),
                ['eventListenerIdentifier' => $eventListenerIdentifier]
            );
        }
        return (int)$highestAppliedSequenceNumber;
    }

    public function releaseHighestAppliedSequenceNumber(): void
    {
        $this->dbal->rollBack();
    }

    /**
     * Saves the $sequenceNumber as the last seen sequence number of events which have been applied to the concrete
     * event listener.
     *
     * @param string $eventListenerIdentifier
     * @param int $sequenceNumber
     * @return void
     */
    public function saveHighestAppliedSequenceNumber(string $eventListenerIdentifier, int $sequenceNumber)
    {
        // TODO: Fails if no matching entry exists
        $this->dbal->update(
            self::TABLE_NAME,
            ['highestappliedsequencenumber' => $sequenceNumber],
            ['eventlisteneridentifier' => $eventListenerIdentifier]
        );
        $this->dbal->commit();
    }
}
