<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventStore;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

final class ExpectedVersion
{
    /**
     * @const int The write should not conflict with anything and should always succeed.
     */
    public const ANY = -2;

    /**
     * @const int The stream should not yet exist. If it does exist treat that as a concurrency problem.
     */
    public const NO_STREAM = -1;

    /**
     * @const int The stream should exist. If it or a metadata stream does not exist treat that as a concurrency problem.
     */
    public const STREAM_EXISTS = -4;

    private function __construct()
    {
    }
}
