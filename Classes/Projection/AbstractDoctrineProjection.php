<?php
namespace Ttree\Cqrs\Projection;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;

/**
 * ProjectionInterface
 */
abstract class AbstractDoctrineProjection implements ProjectionInterface
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
     * @param string $identifier
     * @param string $type
     * @param object $object
     */
    public function persist(string $identifier, string $type, $object)
    {
        $hash = md5($identifier, $type);
        $this->registry->set($hash, $object);
        $this->registry->persist($hash, function () use ($object) {
            $this->flush($object);
        });
    }

    /**
     * @param string $identifier
     * @param string $type
     * @return object
     */
    public function findByIdentifier(string $identifier, string $type)
    {
        $hash = md5($identifier . $type);
        if ($this->registry->has($hash)) {
            return $this->registry->get($hash);
        }
        $object = $this->persistenceManager->getObjectByIdentifier($identifier, $type);
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
