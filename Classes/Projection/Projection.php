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
     * Shortest unambiguous identifier in the form "<package.key>:<projection>", "<key>:<projection>" or "<projection">"
     *
     * @var string
     */
    private $shortIdentifier;

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

    public function __construct(string $fullIdentifier, string $shortIdentifier, string $projectorClassName, array $eventTypes)
    {
        $this->fullIdentifier = $fullIdentifier;
        $this->shortIdentifier = $shortIdentifier;
        $this->projectorClassName = $projectorClassName;
        $this->eventTypes = $eventTypes;
    }

    public function getFullIdentifier(): string
    {
        return $this->fullIdentifier;
    }

    public function getShortIdentifier(): string
    {
        return $this->shortIdentifier;
    }

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
}
