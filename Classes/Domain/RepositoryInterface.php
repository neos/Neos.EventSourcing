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
     * @param string $identifier
     * @return AggregateRootInterface|null
     */
    public function findByIdentifier($identifier): AggregateRootInterface;

    /**
     * @param AggregateRootInterface $aggregate
     * @return void
     */
    public function save(AggregateRootInterface $aggregate);

    /**
     * @param string $identifier
     * @return boolean
     */
    public function contains($identifier): bool;
}
