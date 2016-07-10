<?php
namespace Flowpack\Cqrs\Domain;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * RepositoryInterface
 */
interface RepositoryInterface
{
    /**
     * @param Uuid $aggregateRootId AggregateRoot ID
     * @param string|null $aggregateName Optional name of the AggregateRoot
     * @return AggregateRootInterface|null AggregateRoot
     */
    public function find(Uuid $aggregateRootId, $aggregateName = null);

    /**
     * @param AggregateRootInterface $aggregate
     * @return void
     */
    public function save(AggregateRootInterface $aggregate);
}
