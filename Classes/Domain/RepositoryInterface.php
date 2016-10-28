<?php
namespace Neos\Cqrs\Domain;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * RepositoryInterface
 */
interface RepositoryInterface
{
    /**
     * @param string $identifier
     * @return AggregateRootInterface|null
     */
    public function findByIdentifier($identifier);

    /**
     * @param EventSourcedAggregateRootInterface $aggregate
     * @return void
     */
    public function save(EventSourcedAggregateRootInterface $aggregate);
}
