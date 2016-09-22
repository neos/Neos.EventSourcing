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

use Neos\Cqrs\Projection\AbstractBaseProjection;
use TYPO3\Flow\Persistence\Doctrine\Query;
use TYPO3\Flow\Persistence\QueryResultInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * A base class for Doctrine-based projectors
 *
 * @api
 */
abstract class AbstractDoctrineProjection extends AbstractBaseProjection
{

    /**
     * @Flow\Inject
     * @var DoctrineProjectionPersistenceManager
     */
    protected $projectionPersistenceManager;

    /**
     * Concrete projectors may override this property for setting the default sorting order of query results.
     *
     *  'foo' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
     *  'bar' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING
     *
     * @var array
     * @api
     */
    protected $defaultOrderings = [];

    /**
     * Adds an object to this repository.
     * For use in the concrete projection.
     *
     * @param object $object The object to add
     * @return void
     * @api
     */
    protected function add($object)
    {
        $this->projectionPersistenceManager->add($object);
    }

    /**
     * Schedules a modified object for persistence.
     * For use in the concrete projection.
     *
     * @param object $object The modified object
     * @return void
     * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     * @api
     */
    protected function update($object)
    {
        $this->projectionPersistenceManager->update($object);
    }

    /**
     * Removes an object from this repository.
     * For use in the concrete projection.
     *
     * @param object $object The object to remove
     * @return void
     * @api
     */
    protected function remove($object)
    {
        $this->projectionPersistenceManager->remove($object);
    }

    /**
     * Removes all objects of this repository as if remove() was called for all of them.
     * For usage in the concrete projection.
     *
     * @return void
     * @api
     */
    protected function removeAll()
    {
        foreach ($this->findAll() as $object) {
            $this->remove($object);
        }
    }

    /**
     * Finds all entities in the repository.
     *
     * @return \TYPO3\Flow\Persistence\QueryResultInterface The query result
     * @api
     */
    public function findAll(): QueryResultInterface
    {
        return $this->createQuery()->execute();
    }

    /**
     * Returns a query for objects of this repository
     *
     * @return Query
     * @api
     */
    public function createQuery(): Query
    {
        $query = new Query($this->readModelClassName);
        if ($this->defaultOrderings) {
            $query->setOrderings($this->defaultOrderings);
        }
        return $query;
    }

    /**
     * Returns the number of projections (that is, Read Model instances) exist for this Projector
     *
     * @return int
     * @api
     */
    public function countAll(): int
    {
        return $this->createQuery()->count();
    }

    /**
     * Magic call method for repository methods.
     *
     * Provides three methods
     *  - findBy<PropertyName>($value, $caseSensitive = TRUE, $cacheResult = FALSE)
     *  - findOneBy<PropertyName>($value, $caseSensitive = TRUE, $cacheResult = FALSE)
     *  - countBy<PropertyName>($value, $caseSensitive = TRUE)
     *
     * @param string $method Name of the method
     * @param array $arguments The arguments
     * @return mixed The result of the repository method
     * @api
     */
    public function __call($method, $arguments)
    {
        $query = $this->createQuery();
        $caseSensitive = isset($arguments[1]) ? (boolean)$arguments[1] : true;
        $cacheResult = isset($arguments[2]) ? (boolean)$arguments[2] : false;

        if (isset($method[10]) && strpos($method, 'findOneBy') === 0) {
            $propertyName = lcfirst(substr($method, 9));
            return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->execute($cacheResult)->getFirst();
        } elseif (isset($method[8]) && strpos($method, 'countBy') === 0) {
            $propertyName = lcfirst(substr($method, 7));
            return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->count();
        } elseif (isset($method[7]) && strpos($method, 'findBy') === 0) {
            $propertyName = lcfirst(substr($method, 6));
            return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->execute($cacheResult);
        }

        trigger_error('Call to undefined method ' . get_class($this) . '::' . $method, E_USER_ERROR);
    }
}
