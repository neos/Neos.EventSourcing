<?php
namespace Neos\Cqrs\Projection;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\EventListener\EventListenerInterface;

/**
 * ProjectorInterface
 */
interface ProjectorInterface extends EventListenerInterface
{
    /**
     * Returns the class name of the (main) Read Model of the concrete projector
     *
     * @return string
     */
    public function getReadModelClassName(): string;
}
