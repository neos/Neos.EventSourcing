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

use Neos\Flow\Annotations as Flow;

/**
 * A base class for projectors
 *
 * Specialized projectors may extend this class in order to use the convenience methods included. Alternatively, they
 * can as well just implement the ProjectorInterface and refrain from extending this base class.
 *
 * @api
 */
abstract class AbstractBaseProjector implements ProjectorInterface
{
    /**
     * Concrete projectors may override this property for setting the class name of the Read Model to a non-conventional name
     *
     * @var string
     * @api
     */
    protected $readModelClassName;

    /**
     * Returns the class name of the (main) Read Model of the concrete projector
     *
     * @return string
     */
    public function getReadModelClassName(): string
    {
        return $this->readModelClassName;
    }
}
