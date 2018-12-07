<?php
declare(strict_types=1);
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
     * Removes all objects of this repository as if remove() was called for all of them.
     * For usage in the concrete projector.
     *
     * @return void
     * @api
     */
    public function reset(): void;

    /**
     * Returns true if the projection maintained by the concreted projector does not contain any data (yet).
     *
     * @return boolean
     */
    public function isEmpty(): bool;
}
