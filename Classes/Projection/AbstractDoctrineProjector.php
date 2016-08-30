<?php
namespace Ttree\Cqrs\Projection;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Doctrine\Common\Persistence\ObjectManager;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;

/**
 * ProjectorInterface
 */
abstract class AbstractDoctrineProjector implements ProjectorInterface
{
    /**
     * @var ReadModelRegistry
     * @Flow\Inject
     */
    protected $registry;

    /**
     * @var SystemLoggerInterface
     * @Flow\Inject
     */
    protected $logger;

    /**
     * @var PersistenceManagerInterface
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * @var ObjectManager
     * @Flow\Inject
     */
    protected $entityManager;

    /**
     * @var string
     */
    protected $targetReadModel;

    /**
     * Setup the projector
     */
    public function initializeObject()
    {
        $this->targetReadModel = str_replace(['\\Projection\\'], ['\\ReadModel\\'], get_class($this));
    }

    /**
     * @param string $identifier
     * @param object $object
     */
    public function persist(string $identifier, $object)
    {
        $hash = md5($identifier, $this->targetReadModel);
        $this->registry->set($hash, $object);
        $this->registry->persist($hash, function () use ($object) {
            $this->flush($object);
        });
    }

    /**
     * @param string $identifier
     * @return object
     */
    public function findByIdentifier(string $identifier)
    {
        $hash = md5($identifier . $this->targetReadModel);
        if ($this->registry->has($hash)) {
            return $this->registry->get($hash);
        }
        $object = $this->persistenceManager->getObjectByIdentifier($identifier, $this->targetReadModel);
        if ($object === null) {
            return null;
        }
        $this->registry->set($hash, $object);
        return $object;
    }

    /**
     * @param object $object
     */
    protected function flush($object)
    {
        $this->entityManager->persist($object);
        $this->entityManager->flush($object);
    }
}
