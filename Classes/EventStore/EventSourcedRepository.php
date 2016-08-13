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
use Flowpack\Cqrs\Event\EventBus;
use Flowpack\Cqrs\Event\EventInterface;
use Flowpack\Cqrs\EventStore\Exception\EventStreamNotFoundException;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventSerializer
 */
class EventSourcedRepository implements RepositoryInterface
{
    /**
     * @var EventStoreInterface
     * @Flow\Inject
     */
    protected $eventStore;

    /**
     * @var EventBus
     * @Flow\Inject
     */
    protected $eventBus;

    /**
     * @param string $identifier
     * @param string $aggregateName |null To be sure AR we will get is the proper instance
     * @return AggregateRootInterface
     * @throws AggregateRootNotFoundException
     */
    public function find($identifier, $aggregateName = null): AggregateRootInterface
    {
        try {
            /** @var EventStream $eventStream */
            $eventStream = $this->eventStore->get($identifier);
        } catch (EventStreamNotFoundException $e) {
            throw new AggregateRootNotFoundException(sprintf(
                "AggregateRoot with id '%s' not found", $identifier
            ), 1471077948);
        }

        if ($aggregateName && ($aggregateName !== $eventStream->getAggregateName())) {
            throw new AggregateRootNotFoundException(sprintf(
                "AggregateRoot with id '%s' found, but its name '%s' does not match requested '%s'",
                $identifier,
                $eventStream->getAggregateName(),
                $aggregateName
            ), 1471077957);
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
        try {
            $stream = $this->eventStore
                ->get($aggregate->getAggregateIdentifier());
        } catch (EventStreamNotFoundException $e) {
            $stream = new EventStream(
                $aggregate->getAggregateIdentifier(),
                get_class($aggregate),
                [],
                1
            );
        } finally {
            $uncommitedEvents = $aggregate->pullUncommittedEvents();
            $stream->addEvents($uncommitedEvents);
        }

        $this->eventStore->commit($stream);

        /** @var EventInterface $event */
        foreach ($uncommitedEvents as $event) {
            $this->eventBus->handle($event);
        }
    }
}
