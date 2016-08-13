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
     * @param string $identifier AggregateRoot ID
     * @return AggregateRootInterface|null AggregateRoot
     */
    public function find($identifier): AggregateRootInterface;

    /**
     * @param AggregateRootInterface $aggregate
     * @return void
     */
    public function save(AggregateRootInterface $aggregate);
}
