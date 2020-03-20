<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventPublisher\JobQueue;

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
use Flowpack\JobQueue\Common\Job\JobInterface;
use Flowpack\JobQueue\Common\Queue\Message;
use Flowpack\JobQueue\Common\Queue\QueueInterface;
use Neos\EventSourcing\EventListener\EventListenerInterface;
use Neos\EventSourcing\EventListener\EventListenerInvoker;
use Neos\EventSourcing\EventListener\Exception\EventCouldNotBeAppliedException;
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

final class CatchUpEventListenerJob implements JobInterface
{

    /**
     * @var string
     */
    protected $listenerClassName;

    /**
     * @var string
     */
    protected $eventStoreIdentifier;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(string $listenerClassName, string $eventStoreIdentifier)
    {
        $this->listenerClassName = $listenerClassName;
        $this->eventStoreIdentifier = $eventStoreIdentifier;
    }

    public function getListenerClassName(): string
    {
        return $this->listenerClassName;
    }

    public function getEventStoreIdentifier(): string
    {
        return $this->eventStoreIdentifier;
    }

    /**
     * @param QueueInterface $queue
     * @param Message $message
     * @return bool
     * @throws EventCouldNotBeAppliedException
     */
    public function execute(QueueInterface $queue, Message $message): bool
    {
        /** @var EventListenerInterface $listener */
        $listener = $this->objectManager->get($this->listenerClassName);

        /** @var EventStoreFactory $eventStoreFactory */
        $eventStoreFactory = $this->objectManager->get(EventStoreFactory::class);
        $eventStore = $eventStoreFactory->create($this->eventStoreIdentifier);

        $eventListenerInvoker = new EventListenerInvoker($eventStore, $listener, $this->entityManager->getConnection());
        $eventListenerInvoker->catchUp();
        return true;
    }

    public function getLabel(): string
    {
        return sprintf('Catch up event listener "%s" from store "%s"', $this->listenerClassName, $this->eventStoreIdentifier);
    }
}
