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

use Neos\Cqrs\Exception;
use TYPO3\Flow\Annotations as Flow;

/**
 * An invalid projection identifier exception (thrown if the given identifier does not exist or is ambiguous)
 */
class InvalidProjectionIdentifierException extends Exception
{
}
