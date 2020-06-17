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
 * Exception that is thrown if an event listener contains invalid handler methods (for example if the method and event name don't match)
 */
class InvalidEventListenerException extends \Exception
{
}
