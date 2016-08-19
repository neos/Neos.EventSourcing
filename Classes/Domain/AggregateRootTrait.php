<?php
namespace Ttree\Cqrs\Domain;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Event\EventInterface;
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
     * @param  EventInterface $event
     * @return void
     */
    public function apply(EventInterface $event)
    {
        $this->executeEvent($event);
        $this->events[] = $event;
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

        $nameParts = Arrays::trimExplode('.', $name);
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
