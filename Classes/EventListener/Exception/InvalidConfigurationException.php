<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventListener\Exception;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Exception that is thrown if the event store / listener configuration is not valid (e.g. if a listener for a non-existing event exists)
 */
class InvalidConfigurationException extends \Exception
{
}
