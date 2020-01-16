<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventListener\AppliedEventsStorage;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\EntityManagerInterface;
use Neos\EventSourcing\EventListener\EventListenerInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Default implementation of the AppliedEventsStorageInterface
 *
 * This was formerly the "AppliedEventsLogRepository" but now it is no singleton but instead bound to one specific Event Listener class
 */
final class DefaultAppliedEventsStorage implements AppliedEventsStorageInterface
{
    /**
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var string
     */
    private $eventListenerIdentifier;

    /**
     * @var DoctrineAppliedEventsStorage
     */
    private $_doctrineAdapter;

    /**
     * @param string $eventListenerIdentifier Event Listener Identifier (usually its class name)
     */
    protected function __construct(string $eventListenerIdentifier)
    {
        $this->eventListenerIdentifier = $eventListenerIdentifier;
    }

    /**
     * Creates an instance for the given EventListenerInterface
     *
     * @param EventListenerInterface $listener
     * @return self
     */
    public static function forEventListener(EventListenerInterface $listener): self
    {
        return new static(\get_class($listener));
    }

    /**
     * @inheritDoc
     */
    public function reserveHighestAppliedEventSequenceNumber(): int
    {
        return $this->doctrineAdapter()->reserveHighestAppliedEventSequenceNumber();
    }

    /**
     * @inheritDoc
     */
    public function releaseHighestAppliedSequenceNumber(): void
    {
        $this->doctrineAdapter()->releaseHighestAppliedSequenceNumber();
    }

    /**
     * @inheritDoc
     */
    public function saveHighestAppliedSequenceNumber(int $sequenceNumber): void
    {
        $this->doctrineAdapter()->saveHighestAppliedSequenceNumber($sequenceNumber);
    }

    /**
     * Obtains an instance of the DoctrineAdapter for the bound Event listener
     * and initializes it upon first usage
     *
     * @return DoctrineAppliedEventsStorage
     */
    private function doctrineAdapter(): DoctrineAppliedEventsStorage
    {
        if ($this->_doctrineAdapter === null) {
            $this->_doctrineAdapter = new DoctrineAppliedEventsStorage($this->entityManager->getConnection(), $this->eventListenerIdentifier);
        }
        return $this->_doctrineAdapter;
    }
}
