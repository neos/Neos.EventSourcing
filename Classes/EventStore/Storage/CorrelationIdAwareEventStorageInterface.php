<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventStore\Storage;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\EventStore\EventStream;

interface CorrelationIdAwareEventStorageInterface extends EventStorageInterface
{
    public function loadByCorrelationId(string $correlationId): EventStream;
}
