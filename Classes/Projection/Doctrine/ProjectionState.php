<?php
namespace Neos\Cqrs\Projection\Doctrine;

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
use Doctrine\ORM\Mapping as ORM;

/**
 * Model for storing the projection state of a given Doctrine-based projector
 *
 * @Flow\Entity
 */
class ProjectionState
{
    /**
     * @ORM\Id
     * @var string
     */
    public $projectorIdentifier;

    /**
     * @var integer
     */
    public $highestAppliedSequenceNumber;
}
