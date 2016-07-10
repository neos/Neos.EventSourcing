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

/**
 * AggregateRootTrait
 */
trait AggregateRootTrait
{
    /** @var Uuid */
    protected $id;

    /** @var  string */
    protected $aggregateName;

    /** @var array */
    protected $events = [];

    /**
     * @param  Uuid $uuid
     * @return void
     */
    protected function setId(Uuid $uuid)
    {
        $this->id = $uuid;
    }

    /**
     * @return Uuid
     */
    final public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $aggregateName
     */
    public function setAggregateName($aggregateName)
    {
        $this->aggregateName = $aggregateName;
    }

    /**
     * @return string
     */
    final public function getAggregateName()
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

        $this->setId($stream->getAggregateId());

        /** @var EventInterface $event */
        foreach ($stream as $event) {
            $this->executeEvent($event);
        }
    }

    /**
     * @return array
     */
    public function pullUncommittedEvents()
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

        $method = sprintf('apply%s', ucfirst($name));

        if (!method_exists($this, $method)) {
            throw new \LogicException(sprintf(
                "AR does not contain method '%s' needed for event '%s' to be handled.",
                $method,
                $name
            ));
        }

        $this->$method($event);
    }
}
