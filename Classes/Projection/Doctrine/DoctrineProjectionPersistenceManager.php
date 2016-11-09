<?php
namespace Neos\Cqrs\Projection\Doctrine;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager as DoctrineObjectManager;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\UnitOfWork;
use Neos\Cqrs\Exception;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * A persistence manager for Doctrine-based projectors
 *
 * @Flow\Scope("singleton")
 * @api
 */
class DoctrineProjectionPersistenceManager
{

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @var DoctrineEntityManager
     */
    private $entityManager;

    /**
     * @var int
     */
    private $numberOfPendingChanges = 0;

    /**
     * @param DoctrineObjectManager $entityManager
     * @return void
     */
    public function injectEntityManager(DoctrineObjectManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Returns an object with the given $identifier from persistence.
     *
     * @param string $className
     * @param mixed $identifier
     * @return object
     */
    public function get(string $className, $identifier)
    {
        return $this->entityManager->find($className, $identifier);
    }

    /**
     * Adds an object for persistence.
     *
     * @param object $object The object to add
     * @return void
     */
    public function add($object)
    {
        $this->entityManager->persist($object);
        $this->garbageCollection();
    }

    /**
     * Schedules a modified object for persistence.
     *
     * @param object $object The modified object
     * @throws Exception
     */
    public function update($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException(sprintf('Invalid argument: $object must be an object, %s given.', gettype($object)), 1474531440208);
        }
        if ($this->isNewObject($object)) {
            throw new Exception(sprintf('The object of type %s which was passed to %s->update() is not a previously persisted projection. Check the code which updates this read model and make sure that only objects are updated which were persisted before. Alternatively use add() for persisting new objects.', get_class($object), get_class($this)), 1474531362129);
        }
        try {
            $this->entityManager->persist($object);
        } catch (\Exception $exception) {
            throw new Exception('Could not persist updated object of type "' . get_class($object) . '"', 1474531485464, $exception);
        }
        $this->garbageCollection();
    }

    /**
     * Removes an object from this repository.
     *
     * @param object $object The object to remove
     * @return void
     */
    public function remove($object)
    {
        $this->entityManager->remove($object);
        $this->garbageCollection();
    }

    /**
     * Removes all objects from a Doctrine-based projection.
     *
     * @param string $readModelClassName Read Model class name of the projection
     * @return int Number of records which have been deleted
     */
    public function drop(string $readModelClassName): int
    {
        $query = $this->entityManager->createQuery('DELETE FROM ' . $readModelClassName);
        return $query->execute();
    }

    /**
     * Returns the number of read models stored in this projection.
     *
     * @param string $readModelClassName
     * @return int
     */
    public function count(string $readModelClassName): int
    {
        $query = $this->entityManager->createQuery('SELECT COUNT(m) FROM ' . $readModelClassName . ' m');
        return $query->getSingleScalarResult();
    }

    /**
     * Commits new, updated or removed read model objects which have been registered with add(), update() or remove().
     *
     * @return void
     * @api
     */
    public function persistAll()
    {
        if (!$this->entityManager->isOpen()) {
            $this->systemLogger->log('persistAll() skipped flushing data, the Doctrine EntityManager is closed. Check the logs for error message.', LOG_ERR);
            return;
        }

        try {
            $this->entityManager->flush();
        } catch (ORMException $exception) {
            $this->systemLogger->logException($exception);
            $connection = $this->entityManager->getConnection();
            $connection->close();
            $connection->connect();
            $this->systemLogger->log('Reconnected the Doctrine EntityManager to the persistence backend.', LOG_INFO);
            $this->entityManager->flush();
        }
    }

    /**
     * Persists all pending changes and clears the Doctrine EntityManager if there are more than 100 pending changes
     *
     * @return void
     */
    private function garbageCollection()
    {
        $this->numberOfPendingChanges ++;
        if ($this->numberOfPendingChanges < 100) {
            return;
        }
        $this->numberOfPendingChanges = 0;
        $this->persistAll();
        $this->entityManager->clear();
    }

    /**
     * Checks if the given object has ever been persisted.
     *
     * @param object $object The object to check
     * @return bool true if the object is new, false if the object exists in the repository
     * @api
     */
    public function isNewObject($object): bool
    {
        return ($this->entityManager->getUnitOfWork()->getEntityState($object, UnitOfWork::STATE_NEW) === UnitOfWork::STATE_NEW);
    }
}
