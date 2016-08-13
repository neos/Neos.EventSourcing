<?php
namespace Flowpack\Cqrs\Domain;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Event\EventInterface;
use Flowpack\Cqrs\EventStore\EventStream;
use Flowpack\Cqrs\RuntimeException;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Arrays;

/**
 * AggregateRootTrait
 */
trait AggregateRootTrait
{
    /**
     * @var string
     */
    protected $aggregateIdentifier;

    /**
     * @var string
     */
    protected $aggregateName;

    /**
     * @var array
     */
    protected $events = [];

    /**
     * @param string $identifier
     * @return void
     */
    protected function setAggregateIdentifier($identifier)
    {
        $this->aggregateIdentifier = $identifier;
    }

    /**
     * @return string
     */
    final public function getAggregateIdentifier(): string
    {
        return $this->aggregateIdentifier;
    }

    /**
     * @param string $aggregateName
     */
    protected function setAggregateName($aggregateName)
    {
        $this->aggregateName = $aggregateName;
    }

    /**
     * @return string
     */
    final public function getAggregateName(): string
    {
        return $this->aggregateName;
    }

    /**
     * @param  EventInterface $event
     * @return void
     */
    public function apply(EventInterface $event)
    {
        $this->executeEvent($event);
        $this->events[] = $event;
    }

    /**
     * @param EventStream $stream
     * @throws RuntimeException
     */
    public function reconstituteFromEventStream(EventStream $stream)
    {
        if ($this->events) {
            throw new RuntimeException('AggregateRoot is already reconstituted from event stream.');
        }

        $this->setAggregateIdentifier($stream->getAggregateIdentifier());
        $this->setAggregateName($stream->getAggregateName());

        /** @var EventInterface $event */
        foreach ($stream as $event) {
            $this->executeEvent($event);
        }
    }

    /**
     * @return array
     */
    public function pullUncommittedEvents(): array
    {
        $events = $this->events;

        $this->events = [];

        return $events;
    }

    /**
     * @param  EventInterface $event
     * @return void
     */
    protected function executeEvent(EventInterface $event)
    {
        $name = $event->getName();

        $nameParts = Arrays::trimExplode('\\', $name);
        $className = array_pop($nameParts);

        $method = sprintf('apply%s', ucfirst($className));

        if (!method_exists($this, $method)) {
            throw new \LogicException(sprintf(
                "AR '%s' does not contain method '%s' needed for event '%s' to be handled.",
                get_called_class(),
                $method,
                $name
            ));
        }

        $this->$method($event);
    }
}
