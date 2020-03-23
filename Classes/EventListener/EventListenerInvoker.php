<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventListener;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Neos\EventSourcing\EventListener\AppliedEventsStorage\AppliedEventsStorageInterface;
use Neos\EventSourcing\EventListener\AppliedEventsStorage\DoctrineAppliedEventsStorage;
use Neos\EventSourcing\EventListener\Exception\EventCouldNotBeAppliedException;
use Neos\EventSourcing\EventStore\EventEnvelope;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\StreamAwareEventListenerInterface;
use Neos\EventSourcing\EventStore\StreamName;

final class EventListenerInvoker
{

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var EventListenerInterface
     */
    private $eventListener;

    /**
     * DBAL connection for the DoctrineAppliedEventsStorage (@see getAppliedEventsStorageForListener())
     *
     * @var Connection
     */
    private $connection;

    /**
     * @var \Closure[]
     */
    private $progressCallbacks = [];

    /**
     * How many events should be applied until the whole transaction is committed
     * and the highest applied sequence number is persisted.
     * By default the transaction is committed for every event (batch size = 1)
     *
     * @var int
     */
    private $transactionBatchSize = 1;

    public function __construct(EventStore $eventStore, EventListenerInterface $eventListener, Connection $connection)
    {
        $this->eventStore = $eventStore;
        $this->eventListener = $eventListener;
        $this->connection = $connection;
    }

    /**
     * Register a callback that is invoked for every event that is applied during replay/catchup
     *
     * @param \Closure $callback
     */
    public function onProgress(\Closure $callback): void
    {
        $this->progressCallbacks[] = $callback;
    }

    /**
     * Returns an instance with the transaction batch size.
     * This allows for faster replays/catchups at the cost of an "at least once delivery" if an error occurs.
     *
     * Usage:
     * $eventListenerInvoker = (new EventListenerInvoker($eventStore, $listener, $connection))->withTransactionBatchSize(100)->catchUp($listener);
     *
     * @param int $batchSize
     * @return $this
     */
    public function withTransactionBatchSize(int $batchSize): self
    {
        if ($batchSize < 1) {
            throw new \InvalidArgumentException('The batch size must not be smaller than 1', 1584276378);
        }
        $instance = new static($this->eventStore, $this->eventListener, $this->connection);
        $instance->progressCallbacks = $this->progressCallbacks;
        $instance->transactionBatchSize = $batchSize;
        return $instance;
    }

    /**
     * @throws EventCouldNotBeAppliedException
     */
    public function replay(): void
    {
        $appliedEventsStorage = $this->getAppliedEventsStorage();
        $highestAppliedSequenceNumber = -1;
        $appliedEventsStorage->saveHighestAppliedSequenceNumber($highestAppliedSequenceNumber);
        $this->catchUp();
    }

    /**
     * @throws EventCouldNotBeAppliedException
     */
    public function catchUp(): void
    {
        $appliedEventsStorage = $this->getAppliedEventsStorage();
        $highestAppliedSequenceNumber = $appliedEventsStorage->reserveHighestAppliedEventSequenceNumber();
        $streamName = $this->eventListener instanceof StreamAwareEventListenerInterface ? $this->eventListener::listensToStream() : StreamName::all();
        $eventStream = $this->eventStore->load($streamName, $highestAppliedSequenceNumber + 1);
        $appliedEventsCounter = 0;
        foreach ($eventStream as $eventEnvelope) {
            $sequenceNumber = $eventEnvelope->getRawEvent()->getSequenceNumber();
            if ($sequenceNumber <= $highestAppliedSequenceNumber) {
                continue;
            }
            try {
                $this->applyEvent($eventEnvelope);
            } catch (EventCouldNotBeAppliedException $exception) {
                $appliedEventsStorage->releaseHighestAppliedSequenceNumber();
                throw $exception;
            }
            $appliedEventsCounter ++;
            $appliedEventsStorage->saveHighestAppliedSequenceNumber($eventEnvelope->getRawEvent()->getSequenceNumber());
            if ($this->transactionBatchSize === 1 || $appliedEventsCounter % $this->transactionBatchSize === 0) {
                $appliedEventsStorage->releaseHighestAppliedSequenceNumber();
                $highestAppliedSequenceNumber = $appliedEventsStorage->reserveHighestAppliedEventSequenceNumber();
            } else {
                $highestAppliedSequenceNumber = $sequenceNumber;
            }
            foreach ($this->progressCallbacks as $callback) {
                $callback($eventEnvelope);
            }
        }
        $appliedEventsStorage->releaseHighestAppliedSequenceNumber();
    }

    /**
     * @param EventEnvelope $eventEnvelope
     * @throws EventCouldNotBeAppliedException
     */
    private function applyEvent(EventEnvelope $eventEnvelope): void
    {
        $event = $eventEnvelope->getDomainEvent();
        $rawEvent = $eventEnvelope->getRawEvent();
        try {
            $listenerMethodName = 'when' . (new \ReflectionClass($event))->getShortName();
        } catch (\ReflectionException $exception) {
            throw new \RuntimeException(sprintf('Could not extract listener method name for listener %s and event %s', get_class($this->eventListener), get_class($event)), 1541003718, $exception);
        }
        if (!method_exists($this->eventListener, $listenerMethodName)) {
            return;
        }
        if ($this->eventListener instanceof BeforeInvokeInterface) {
            $this->eventListener->beforeInvoke($eventEnvelope);
        }
        try {
            $this->eventListener->$listenerMethodName($event, $rawEvent);
        } catch (\Throwable $exception) {
            throw new EventCouldNotBeAppliedException(sprintf('Event "%s" (%s) could not be applied to %s. Sequence number (%d) is not updated', $rawEvent->getIdentifier(), $rawEvent->getType(), get_class($this->eventListener), $rawEvent->getSequenceNumber()), 1544207001, $exception, $eventEnvelope, $this->eventListener);
        }
        if ($this->eventListener instanceof AfterInvokeInterface) {
            $this->eventListener->afterInvoke($eventEnvelope);
        }
    }

    private function getAppliedEventsStorage(): AppliedEventsStorageInterface
    {
        if ($this->eventListener instanceof ProvidesAppliedEventsStorageInterface) {
            $appliedEventsStorage = $this->eventListener->getAppliedEventsStorage();
        } elseif ($this->eventListener instanceof AppliedEventsStorageInterface) {
            $appliedEventsStorage = $this->eventListener;
        } else {
            $appliedEventsStorage = new DoctrineAppliedEventsStorage($this->connection, \get_class($this->eventListener));
        }
        return $appliedEventsStorage;
    }
}
