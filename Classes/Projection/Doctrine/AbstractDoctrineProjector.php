<?php
namespace Neos\EventSourcing\Projection\Doctrine;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Exception;
use Neos\EventSourcing\Projection\ProjectorInterface;
use Neos\Flow\Annotations as Flow;

/**
 * A base class for Doctrine-based projectors
 *
 * @api
 */
abstract class AbstractDoctrineProjector implements ProjectorInterface
{
    /**
     * @Flow\Inject
     * @var DoctrineProjectionState
     */
    protected $projectionPersistenceManager;

    /**
     * Concrete projectors may override this property for setting the class name of the Read Model to a non-conventional name
     *
     * @var string
     * @api
     */
    protected $readModelClassName;

    /**
     * The state of this projection
     *
     * @var DoctrineProjectionState
     * @api
     */
    protected $state;

    /**
     * Returns the class name of the (main) Read Model of the concrete projector
     *
     * @return string
     */
    public function getReadModelClassName(): string
    {
        return $this->readModelClassName;
    }

    /**
     * Initialize the Read Model class name
     * Make sure to call this method as parent when overriding it in a concrete projector.
     *
     * @return void
     * @throws Exception
     */
    protected function initializeObject()
    {
        if ($this->readModelClassName === null) {
            if (substr(get_class($this), -9, 9) !== 'Projector') {
                throw new Exception(sprintf('The class name "%s" doesn\'t end in "Projector" so the Read Model class name can\'t be determined automatically. Please set the "readModelClassName" field it in your concrete projector.', get_class($this)), 1476799474);
            }
            $this->readModelClassName = substr(get_class($this), 0, -9);
        }
        $this->state = new DoctrineProjectionState($this->readModelClassName);
    }

    /**
     * @inheritdoc
     *
     * @return void
     * @api
     */
    public function reset()
    {
        $this->state->reset();
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->state->isEmpty();
    }

}
