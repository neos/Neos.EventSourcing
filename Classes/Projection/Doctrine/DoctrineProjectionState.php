<?php
namespace Neos\EventSourcing\Projection\Doctrine;

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
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\UnitOfWork;
use Neos\EventSourcing\Exception;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Annotations as Flow;

/**
 * A state for Doctrine-based projectors
 *
 * @api
 */
class DoctrineProjectionState
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
     * @var string
     */
    private $readModelClassName;

    /**
     * @param string $readModelClassName
     */
    public function __construct($readModelClassName)
    {
        $this->readModelClassName = $readModelClassName;
    }

    /**
     * @param DoctrineObjectManager $entityManager
     * @return void
     */
    public function injectEntityManager(DoctrineObjectManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Retrieves an object with the given $identifier.
     * For use in the concrete projector.
     *
     * @param string $identifier
     * @return object an instance of $this->readModelClassName or NULL if no matching object could be found
     * @api
     */
    public function get($identifier)
    {
        return $this->entityManager->find($this->readModelClassName, $identifier);
    }

    /**
     * Adds an object to this repository.
     * For use in the concrete projector.
     *
     * @param object $object The object to add
     * @return void
     * @api
     */
    public function add($object)
    {
        $this->entityManager->persist($object);
        $this->garbageCollection();
    }

    /**
     * Schedules a modified object for persistence.
     * For use in the concrete projector.
     *
     * @param object $object The modified object
     * @return void
     * @throws Exception
     * @api
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
     * Removes an object from the projector's persistence.
     * For use in the concrete projector.
     *
     * @param object $object The object to remove
     * @return void
     * @api
     */
    public function remove($object)
    {
        $this->entityManager->remove($object);
        $this->garbageCollection();
    }

    /**
     * Removes all objects of this repository as if remove() was called for all of them.
     * For usage in the concrete projector.
     *
     * @return void
     * @api
     */
    public function reset()
    {
        $query = $this->entityManager->createQuery('DELETE FROM ' . $this->readModelClassName);
        $query->execute();
    }

    /**
     * If this projection is currently empty
     *
     * @return bool
     * @api
     */
    public function isEmpty(): bool
    {
        $query = $this->entityManager->createQuery('SELECT COUNT(m) FROM ' . $this->readModelClassName . ' m');
        return $query->getSingleScalarResult() === 0;
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

    /**
     * @return void
     */
    public function shutdownObject()
    {
        $this->persistAll();
    }
}
