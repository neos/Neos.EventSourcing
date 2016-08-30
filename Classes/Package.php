<?php
namespace Ttree\Cqrs;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Projection\ReadModelRegistry;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Package\Package as BasePackage;

/**
 * The Ttree.Cqrs Package
 */
class Package extends BasePackage
{
    /**
     * @var boolean
     */
    protected $protected = true;

    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(Bootstrap::class, 'finishedRuntimeRun', ReadModelRegistry::class, 'flush');
    }
}
