<?php
namespace Ttree\Cqrs\Projector;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\EventListener\EventListenerInterface;
use Ttree\Cqrs\Projection\DoctrineProjectionRegistry;
use TYPO3\Flow\Annotations as Flow;

/**
 * ProjectorInterface
 */
abstract class AbstractDoctrineProjector implements ProjectorInterface
{
    /**
     * @var DoctrineProjectionRegistry
     * @Flow\Inject
     */
    protected $registry;
}
