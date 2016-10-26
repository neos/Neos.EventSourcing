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
 * Base implementation for an event-sourced repository
 */
abstract class AbstractEventSourcedRepository implements RepositoryInterface
{
    /**
     * @var EventStore
     * @Flow\Inject
     */
    protected $eventStore;


    /**
     * @var EventBus
     * @Flow\Inject
     */
    protected $eventBus;

    /**
     * @Flow\Inject
     * @var StreamNameResolver
     */
    protected $streamNameResolver;

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
    final public function findByIdentifier($identifier)
    {
        $streamName = $this->streamNameResolver->getStreamNameForAggregateTypeAndIdentifier($this->aggregateClassName, $identifier);
        try {
            $eventStream = $this->eventStore->get($streamName);
        } catch (EventStreamNotFoundException $e) {
            return null;
        }

        if (!class_exists($this->aggregateClassName)) {
            throw new AggregateRootNotFoundException(sprintf("Could not reconstitute the aggregate root %s because its class '%s' does not exist.", $identifier, $this->aggregateClassName), 1474454928115);
        }
        if (!is_subclass_of($this->aggregateClassName, EventSourcedAggregateRootInterface::class)) {
            throw new AggregateRootNotFoundException(sprintf("Could not reconstitute the aggregate root '%s' with id '%s' because it does not implement the EventSourcedAggregateRootInterface.", $this->aggregateClassName, $identifier, $this->aggregateClassName), 1474464335530);
        }
        return call_user_func($this->aggregateClassName . '::reconstituteFromEventStream', $identifier, $eventStream);
    }

    /**
     * @param EventSourcedAggregateRootInterface $aggregate
     * @return void
     */
    final public function save(EventSourcedAggregateRootInterface $aggregate)
    {
        $streamName = $this->streamNameResolver->getStreamNameForAggregate($aggregate);
        try {
            $stream = $this->eventStore->get($streamName);
        } catch (EventStreamNotFoundException $e) {
            $stream = new EventStream();
        }

        $uncommittedEvents = $aggregate->pullUncommittedEvents();
        $stream->addEvents(...$uncommittedEvents);

        $this->eventStore->commit($streamName, $stream, function ($version) use ($uncommittedEvents) {
            /** @var EventTransport $eventTransport */
            foreach ($uncommittedEvents as $eventTransport) {
                // @todo metadata enrichment must be done in external service, with some middleware support
                $versionedMetadata = $eventTransport->getMetadata()->withProperty(Metadata::VERSION, $version);
                $this->eventBus->handle($eventTransport->withMetadata($versionedMetadata));
            }
        });
    }
}
