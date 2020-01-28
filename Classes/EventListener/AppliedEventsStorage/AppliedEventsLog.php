<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventListener\AppliedEventsStorage;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * Model for storing the applied events state of a given asynchronous event listener
 *
 * Note: This class is merely used in order for the Doctrine Schema mapping, it is not meant to be used otherwise
 *
 * @Flow\Entity
 * @ORM\Table(name=AppliedEventsLog::TABLE_NAME)
 * @internal
 */
class AppliedEventsLog
{
    public const TABLE_NAME = 'neos_eventsourcing_eventlistener_appliedeventslog';

    private function __construct()
    {
        // This class is not meant to be instantiated
    }

    /**
     * @ORM\Id
     * @var string
     */
    public $eventListenerIdentifier;

    /**
     * @var int
     */
    public $highestAppliedSequenceNumber;
}
