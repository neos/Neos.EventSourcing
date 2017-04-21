<?php
namespace Neos\EventSourcing\Projection;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\EventListener\EventListenerInterface;

/**
 * ProjectorInterface
 */
interface ProjectorInterface extends EventListenerInterface
{
    /**
     * Returns the class name of the (main) Read Model of the concrete projector
     *
     * @return string
     * @api
     */
    public function getReadModelClassName(): string;

    /**
     * Removes all data of this projections state as if remove() was called for all of them.
     * For usage in the concrete projector.
     *
     * @return void
     * @api
     */
    public function reset();

    /**
     * Returns true if the projection state maintained by the concrete projector does not contain any data (yet).
     *
     * @return boolean
     */
    public function isEmpty();
}
