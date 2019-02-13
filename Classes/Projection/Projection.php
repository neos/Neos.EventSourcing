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
    private $eventClassNames;

    /**
     * @param string $identifier
     * @param string $projectorClassName
     * @param string[] $eventClassNames
     */
    public function __construct(string $identifier, string $projectorClassName, array $eventClassNames)
    {
        $this->fullIdentifier = $identifier;
        $this->projectorClassName = $projectorClassName;
        $this->eventClassNames = $eventClassNames;
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
     * @return string[]
     */
    public function getEventClassNames(): array
    {
        return $this->eventClassNames;
    }
}
