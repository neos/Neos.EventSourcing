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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\Exception\DatabaseConnectionException;
use Neos\Flow\Persistence\Doctrine\Query;
use Neos\Flow\Persistence\QueryResultInterface;

/**
 * A base class for Doctrine-based Finders
 *
 * @api
 */
abstract class AbstractDoctrineFinder
{
    /**
     * @Flow\Inject
     * @var DoctrineProjectionPersistenceManager
     */
    protected $projectionPersistenceManager;

    /**
     * Concrete projectors may override this property for setting the default sorting order of query results.
     *
     *  'foo' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
     *  'bar' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING
     *
     * @var array
     * @api
     */
    protected $defaultOrderings = [];

    /**
     * Concrete Finders may override this property for setting the class name of the Read Model to a non-conventional name
     *
     * @var string
     * @api
     */
    protected $readModelClassName;

    /**
     * Initialize the Read Model class name
     * Make sure to call this method as parent when overriding it in a concrete finder.
     *
     * @return void
     */
    protected function initializeObject(): void
    {
        if ($this->readModelClassName === null && substr(get_class($this), -6, 6) === 'Finder') {
            $this->readModelClassName = substr(get_class($this), 0, -6);
        }
    }

    /**
     * Finds all entities in the repository.
     *
     * @return QueryResultInterface The query result
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
     * @throws DatabaseConnectionException
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
        }
        if (isset($method[8]) && strpos($method, 'countBy') === 0) {
            $propertyName = lcfirst(substr($method, 7));
            return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->count();
        }
        if (isset($method[7]) && strpos($method, 'findBy') === 0) {
            $propertyName = lcfirst(substr($method, 6));
            return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->execute($cacheResult);
        }

        trigger_error('Call to undefined method ' . get_class($this) . '::' . $method, E_USER_ERROR);

        // to avoid "inconsistent return points"
        return null;
    }
}
