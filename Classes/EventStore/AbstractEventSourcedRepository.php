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

use Neos\Cqrs\Domain\EventSourcedAggregateRootInterface;
use Neos\Cqrs\Domain\Exception\AggregateRootNotFoundException;
use Neos\Cqrs\Domain\RepositoryInterface;
use Neos\Cqrs\Event\EventPublisher;
use Neos\Flow\Annotations as Flow;

/**
 * Base implementation for an event-sourced repository
 */
abstract class AbstractEventSourcedRepository implements RepositoryInterface
{
    /**
     * @Flow\Inject
     * @var EventStoreManager
     */
    protected $eventStoreManager;

    /**
     * @Flow\Inject
     * @var EventPublisher
     */
    protected $eventPublisher;

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
     * Return an aggregate instance specified by the identifier.
     *
     * @param string $identifier
     * @return EventSourcedAggregateRootInterface
     * @throws AggregateRootNotFoundException
     */
    public function get(string $identifier)
    {
        $streamName = $this->streamNameResolver->getStreamNameForAggregateTypeAndIdentifier($this->aggregateClassName, $identifier);
        $eventStore = $this->eventStoreManager->getEventStoreForAggregateStreamName($streamName);
        $eventStream = $eventStore->get(new StreamNameFilter($streamName));

        if (!class_exists($this->aggregateClassName)) {
            throw new AggregateRootNotFoundException(sprintf("Could not reconstitute the aggregate root %s because its class '%s' does not exist.", $identifier, $this->aggregateClassName), 1474454928115);
        }
        if (!is_subclass_of($this->aggregateClassName, EventSourcedAggregateRootInterface::class)) {
            throw new AggregateRootNotFoundException(sprintf("Could not reconstitute the aggregate root '%s' with id '%s' because it does not implement the EventSourcedAggregateRootInterface.", $this->aggregateClassName, $identifier, $this->aggregateClassName), 1474464335530);
        }

        return call_user_func($this->aggregateClassName . '::reconstituteFromEventStream', $identifier, $eventStream);
    }

    /**
     * Save the given aggregate instance
     *
     * For purposes of optimistic locking, an expected version number can be specified.
     *
     * @param EventSourcedAggregateRootInterface $aggregate The aggregate to save
     * @param int $expectedVersion The version of the aggregate the changes to be saved are based on
     * @return void
     */
    public function save(EventSourcedAggregateRootInterface $aggregate, int $expectedVersion = null)
    {
        $streamName = $this->streamNameResolver->getStreamNameForAggregate($aggregate);
        if ($expectedVersion === null) {
            $expectedVersion = $aggregate->getReconstitutionVersion();
        }
        $this->eventPublisher->publishMany($streamName, $aggregate->pullUncommittedEvents(), $expectedVersion);
    }
}
