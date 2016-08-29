<?php
namespace Ttree\Cqrs\Projection;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;

/**
 * DoctrineProjectionRegistry
 *
 * @Flow\Scope("singleton")
 */
class DoctrineProjectionRegistry
{
    /**
     * @var array
     */
    protected $projections = [];

    /**
     * @var PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @param string $identifier
     * @param string $objectType
     * @return object|null
     */
    public function findByIdentifier(string $identifier, string $objectType)
    {
        $hash = md5($identifier . $objectType);
        if (isset($this->projections[$hash])) {
            return $this->projections[$hash];
        }
        $object = $this->persistenceManager->getObjectByIdentifier($identifier, $objectType);
        if ($object === null) {
            return null;
        }
        $this->projections[$hash] = $object;
        return $object;
    }

    /**
     * @param string $identifier
     * @param string $objectType
     * @param $object
     */
    public function add(string $identifier, string $objectType, $object)
    {
        $hash = md5($identifier . $objectType);
        $this->projections[$hash] = $object;
    }

    /**
     * @return void
     */
    public function flush()
    {
        array_map(function ($object) {
            $this->persistenceManager->whitelistObject($object);
            if ($this->persistenceManager->isNewObject($object)) {
                $this->persistenceManager->add($object);
            } else {
                $this->persistenceManager->update($object);
            }
        }, $this->projections);
        $this->persistenceManager->persistAll(true);
    }
}
