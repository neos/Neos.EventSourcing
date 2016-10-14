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
    private $packageKey;

    /**
     * @var string
     */
    private $projectorClassName;

    public function __construct(string $fullIdentifier, string $shortIdentifier, string $packageKey, string $projectorClassName)
    {
        $this->fullIdentifier = $fullIdentifier;
        $this->shortIdentifier = $shortIdentifier;
        $this->packageKey = $packageKey;
        $this->projectorClassName = $projectorClassName;
    }

    /**
     * @return string
     */
    public function getFullIdentifier()
    {
        return $this->fullIdentifier;
    }

    /**
     * @return string
     */
    public function getShortIdentifier()
    {
        return $this->shortIdentifier;
    }

    /**
     * @return string
     */
    public function getPackageKey()
    {
        return $this->packageKey;
    }

    /**
     * @return string
     */
    public function getProjectorClassName()
    {
        return $this->projectorClassName;
    }

}