<?php
namespace Neos\Cqrs\ProcessManager\State;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\ObjectManager as DoctrineObjectManager;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;

/**
 * A repository specialized on Process States
 *
 * @Flow\Scope("singleton")
 */
final class StateRepository
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
     * @param DoctrineObjectManager $entityManager
     * @return void
     */
    public function injectEntityManager(DoctrineObjectManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $identifier
     * @param string $processManagerClassName
     * @return object
     */
    public function get(string $identifier, string $processManagerClassName)
    {
        return $this->entityManager->find(ProcessState::class, ['identifier' => $identifier, 'processManagerClassName' => $processManagerClassName]);
    }

    /**
     * @param ProcessState $state The State to save
     * @return void
     */
    public function save(ProcessState $state)
    {
        $this->entityManager->persist($state);
        $this->entityManager->flush();
    }

    /**
     * @param ProcessState $state
     */
    public function remove(ProcessState $state)
    {
        $this->entityManager->remove($state);
        $this->entityManager->flush();
    }
}
