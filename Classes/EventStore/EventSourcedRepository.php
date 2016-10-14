<?php
namespace Neos\Cqrs\EventStore;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Domain\AggregateRootInterface;
use Neos\Cqrs\Domain\EventSourcedAggregateRootInterface;
use Neos\Cqrs\Domain\Exception\AggregateRootNotFoundException;
use Neos\Cqrs\Domain\RepositoryInterface;
use Neos\Cqrs\Event\EventBus;
use Neos\Cqrs\Event\EventTransport;
use Neos\Cqrs\Event\Metadata;
use Neos\Cqrs\EventStore\Exception\EventStreamNotFoundException;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventSourcedRepository
 */
abstract class EventSourcedRepository implements RepositoryInterface
{
    /**
     * @Flow\Inject
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @Flow\Inject
     * @var EventBus
     */
    protected $eventBus;

    /**
     * @Flow\Inject
     * @var EventStreamResolver
     */
    protected $eventStreamResolver;

    /**
     * @var string
     */
    protected $aggregateClassName;

    /**
     * Initializes a new Repository.
     */
    public function __construct()
    {
        $this->aggregateClassName = preg_replace(['/Repository$/'], [''], get_class($this));
    }

    /**
     * @param string $identifier
     * @return AggregateRootInterface
     * @throws AggregateRootNotFoundException
     */
    public function findByIdentifier($identifier)
    {
        $filter = new EventStreamFilter();
        $filter->streamName = $this->eventStreamResolver->getStreamNameForAggregateTypeAndIdentifier($this->aggregateClassName, $identifier);
        try {
            /** @var EventStream $eventStream */
            $eventStream = $this->eventStore->get($filter);
        } catch (EventStreamNotFoundException $e) {
            return null;
        }

        if (!class_exists($this->aggregateClassName)) {
            throw new AggregateRootNotFoundException(sprintf("Could not reconstitute the aggregate root %s because its class '%s' does not exist.", $identifier, $this->aggregateClassName), 1474454928115);
        }

        $aggregateRoot = unserialize('O:' . strlen($this->aggregateClassName) . ':"' . $this->aggregateClassName . '":1:{s:13:"' . chr(0) . '*' . chr(0) . 'identifier";s:36:"' . $identifier . '";}');
        if (!$aggregateRoot instanceof EventSourcedAggregateRootInterface) {
            throw new AggregateRootNotFoundException(sprintf("Could not reconstitute the aggregate root '%s' with id '%s' because it does not implement the EventSourcedAggregateRootInterface.", $this->aggregateClassName, $identifier, $this->aggregateClassName), 1474464335530);
        }
        $aggregateRoot->reconstituteFromEventStream($eventStream);
        return $aggregateRoot;
    }

    /**
     * @param  AggregateRootInterface $aggregate
     * @return void
     */
    public function save(AggregateRootInterface $aggregate)
    {
        $eventStreamName = $this->eventStreamResolver->getStreamNameForAggregate($aggregate);
        $filter = new EventStreamFilter();
        $filter->streamName = $eventStreamName;
        try {
            $stream = $this->eventStore->get($filter);
        } catch (EventStreamNotFoundException $e) {
            $stream = new EventStream();
        }

        $uncommittedEvents = $aggregate->pullUncommittedEvents();
        $stream->addEvents(...$uncommittedEvents);

        $this->eventStore->commit($eventStreamName, $stream, function ($version) use ($uncommittedEvents) {
            /** @var EventTransport $eventTransport */
            foreach ($uncommittedEvents as $eventTransport) {
                // @todo metadata enrichment must be done in external service, with some middleware support
                $versionedMetadata = $eventTransport->getMetadata()->withProperty(Metadata::VERSION, $version);
                $this->eventBus->handle($eventTransport->withMetadata($versionedMetadata));
            }
        });
    }
}
