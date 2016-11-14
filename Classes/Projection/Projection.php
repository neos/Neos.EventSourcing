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

use Neos\Cqrs\EventListener\AsynchronousEventListenerInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * Projection DTO
 */
class Projection
{
    /**
     * identifier in the form "<package.key>:<projection>"
     *
     * @var string
     */
    private $fullIdentifier;

    /**
     * @var string
     */
    private $projectorClassName;

    /**
     * Array of event types this projection listens to
     *
     * @var string[]
     */
    private $eventTypes;

    /**
     * @param string $identifier
     * @param string $projectorClassName
     * @param array $eventTypes
     */
    public function __construct(string $identifier, string $projectorClassName, array $eventTypes)
    {
        $this->fullIdentifier = $identifier;
        $this->projectorClassName = $projectorClassName;
        $this->eventTypes = $eventTypes;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->fullIdentifier;
    }

    /**
     * @return string
     */
    public function getProjectorClassName(): string
    {
        return $this->projectorClassName;
    }

    /**
     * @return \string[]
     */
    public function getEventTypes(): array
    {
        return $this->eventTypes;
    }

    /**
     * @return bool
     */
    public function isAsynchronous(): bool
    {
        return is_subclass_of($this->getProjectorClassName(), AsynchronousEventListenerInterface::class);
    }
}
