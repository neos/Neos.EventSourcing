<?php
namespace Neos\EventSourcing\EventStore;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Domain\EventSourcedAggregateRootInterface;
use Neos\EventSourcing\Domain\Exception\AggregateRootNotFoundException;
use Neos\EventSourcing\Domain\RepositoryInterface;
use Neos\EventSourcing\Event\EventPublisher;
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
        return $this->loadByTypeAndIdentifier($this->aggregateClassName, $identifier);
    }

    /**
     * Return an aggregate instance of the given class specified by the identifier.
     *
     * @param string $aggregateClassName
     * @param string $identifier
     * @return EventSourcedAggregateRootInterface
     * @throws AggregateRootNotFoundException
     */
    public function loadByTypeAndIdentifier(string $aggregateClassName, string $identifier)
    {
        $streamName = $this->streamNameResolver->getStreamNameForAggregateTypeAndIdentifier($aggregateClassName, $identifier);
        $eventStore = $this->eventStoreManager->getEventStoreForStreamName($streamName);
        $eventStream = $eventStore->get(new StreamNameFilter($streamName));

        if (!class_exists($aggregateClassName)) {
            throw new AggregateRootNotFoundException(sprintf("Could not reconstitute the aggregate root %s because its class '%s' does not exist.", $identifier, $aggregateClassName), 1474454928115);
        }
        if (!is_subclass_of($aggregateClassName, EventSourcedAggregateRootInterface::class)) {
            throw new AggregateRootNotFoundException(sprintf("Could not reconstitute the aggregate root '%s' with id '%s' because it does not implement the EventSourcedAggregateRootInterface.", $aggregateClassName, $identifier), 1474464335530);
        }

        return call_user_func($aggregateClassName . '::reconstituteFromEventStream', $identifier, $eventStream);
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
