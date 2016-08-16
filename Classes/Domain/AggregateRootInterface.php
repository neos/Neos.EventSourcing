<?php
namespace Flowpack\Cqrs\Domain;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Event\EventInterface;
use Flowpack\EventStore\EventStream;
use TYPO3\Flow\Annotations as Flow;

/**
 * AggregateRootInterface
 */
interface AggregateRootInterface
{
    /**
     * @return string
     */
    public function getAggregateIdentifier(): string;

    /**
     * @param EventInterface $event
     * @return void
     */
    public function apply(EventInterface $event);

    /**
     * @return array
     */
    public function pullUncommittedEvents(): array;
}
