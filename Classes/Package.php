<?php
namespace Neos\Cqrs;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Projection\Doctrine\DoctrineProjectionPersistenceManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;

/**
 * The Neos.Cqrs Package
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
        $dispatcher->connect(Bootstrap::class, 'finishedRuntimeRun', DoctrineProjectionPersistenceManager::class, 'persistAll');
    }
}
