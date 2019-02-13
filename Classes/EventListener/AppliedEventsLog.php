<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventListener;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * Model for storing the applied events state of a given asynchronous event listener
 *
 * @Flow\Entity
 */
class AppliedEventsLog
{
    /**
     * @ORM\Id
     * @var string
     */
    public $eventListenerIdentifier;

    /**
     * @var string
     */
    public $highestAppliedSequenceNumber;
}
