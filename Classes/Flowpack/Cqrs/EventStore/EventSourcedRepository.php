<?php
namespace Flowpack\Cqrs\EventStore;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Domain\AggregateRootInterface;
use Flowpack\Cqrs\Domain\Exception\AggregateRootNotFoundException;
use Flowpack\Cqrs\Domain\RepositoryInterface;
use Flowpack\Cqrs\Domain\Uuid;
use Flowpack\Cqrs\Event\EventBus;
use Flowpack\Cqrs\Event\EventInterface;
use Flowpack\Cqrs\EventStore\Exception\EventStreamNotFoundException;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventSerializer
 */
class EventSourcedRepository implements RepositoryInterface
{
    /** @var EventStoreInterface */
    protected $eventStore;

    /** @var EventBus */
    protected $eventBus;

    /**
     * @param EventStoreInterface $eventStore
     * @param EventBus $eventBus
     */
    public function __construct(EventStoreInterface $eventStore, EventBus $eventBus)
    {
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
    }

    /**
     * @param Uuid $aggregateRootId
     * @param string $aggregateName |null To be sure AR we will get is the proper instance
     * @return AggregateRootInterface
     * @throws AggregateRootNotFoundException
     */
    public function find(Uuid $aggregateRootId, $aggregateName = null)
    {
        try {
            /** @var EventStream $eventStream */
            $eventStream = $this->eventStore->get($aggregateRootId);
        } catch (EventStreamNotFoundException $e) {
            throw new AggregateRootNotFoundException(sprintf(
                "AggregateRoot with id '%s' not found", $aggregateRootId
            ));
        }

        if ($aggregateName && ($aggregateName !== $eventStream->getAggregateName())) {
            throw new AggregateRootNotFoundException(sprintf(
                "AggregateRoot with id '%s' found, but its name '%s' does not match requested '%s'",
                $aggregateRootId,
                $eventStream->getAggregateName(),
                $aggregateName
            ));
        }

        $reflection = new \ReflectionClass($eventStream->getAggregateName());

        /** @var AggregateRootInterface $aggregateRoot */
        $aggregateRoot = $reflection->newInstanceWithoutConstructor();
        $aggregateRoot->reconstituteFromEventStream($eventStream);

        return $aggregateRoot;
    }

    /**
     * @param  AggregateRootInterface $aggregate
     * @return void
     */
    public function save(AggregateRootInterface $aggregate)
    {
        /** @var Uuid $id */
        $id = $aggregate->getId();

        /** @var array $newEvents */
        $events = $aggregate->pullUncommittedEvents();

        try {

            /** @var EventStream $stream */
            $stream = $this->eventStore->get($id);
            $stream->addEvents($newEvents);

        } catch (EventStreamNotFoundException $e) {

            $stream = new EventStream(
                $aggregate->getId(),
                get_class($aggregate),
                $newEvents,
                1
            );

        }

        $this->eventStore->commit($stream);

        /** @var EventInterface $event */
        foreach ($events as $event) {
            $this->eventBus->handle($event);
        }
    }
}
