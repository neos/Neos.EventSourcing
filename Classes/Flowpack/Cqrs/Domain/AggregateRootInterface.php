<?php
namespace Flowpack\Cqrs\Domain;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Event\EventInterface;
use Flowpack\Cqrs\EventStore\EventStream;
use TYPO3\Flow\Annotations as Flow;

/**
 * AggregateRootInterface
 */
interface AggregateRootInterface
{
    /**
     * @return Uuid
     */
    public function getId();

    /**
     * @return string
     */
    public function getAggregateName();

    /**
     * @param EventInterface $event
     * @return void
     */
    public function apply(EventInterface $event);

    /**
     * @param EventStream $stream
     * @return void
     */
    public function reconstituteFromEventStream(EventStream $stream);

    /**
     * @return EventStream
     */
    public function pullUncommittedEvents();
}
