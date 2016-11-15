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
use Neos\Cqrs\EventListener\AsynchronousEventListenerInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * A base class for Doctrine-based projectors
 *
 * @api
 */
abstract class AbstractAsynchronousDoctrineProjector extends AbstractDoctrineProjector implements AsynchronousEventListenerInterface
{
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
     * Returns the last seen sequence number of events which has been applied to the concrete event listener.
     *
     * @return int
     */
    public function getHighestAppliedSequenceNumber(): int
    {
        $projectorIdentifier = $this->renderProjectorIdentifier();
        $projectionState = $this->entityManager->find(ProjectionState::class, $projectorIdentifier);
        return ($projectionState instanceof ProjectionState ? $projectionState->highestAppliedSequenceNumber : 0);
    }

    /**
     * Saves the $sequenceNumber as the last seen sequence number of events which have been applied to the concrete
     * event listener.
     *
     * @param int $sequenceNumber
     * @return void
     */
    public function saveHighestAppliedSequenceNumber(int $sequenceNumber)
    {
        $projectorIdentifier = $this->renderProjectorIdentifier();
        $projectionState = $this->entityManager->find(ProjectionState::class, $projectorIdentifier);
        if ($projectionState === null) {
            $projectionState = new ProjectionState();
            $projectionState->projectorIdentifier = $projectorIdentifier;
        }
        $projectionState->highestAppliedSequenceNumber = $sequenceNumber;
        $this->entityManager->persist($projectionState);
    }

    /**
     * Renders a projector identifier which can be used as an id in the projection state
     *
     * @return string
     */
    private function renderProjectorIdentifier(): string
    {
        $identifier = strtolower(str_replace('\\', '_', get_class($this)));
        if (strlen($identifier) > 255) {
            $identifier = substr($identifier, 0, 255 - 6) . '_' . substr(sha1($identifier), 0, 5);
        }
        return $identifier;
    }
}
