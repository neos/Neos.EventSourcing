<?php
namespace Neos\Cqrs\Domain;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Timestamp
 */
class Timestamp
{
    const OUTPUT_FORMAT = 'Y-m-d\TH:i:s.uO';

    /**
     * Create a new DateTimeImmutable object based on the current (micro-) time.
     *
     * @return \DateTimeImmutable
     */
    public static function create(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat(
            'U.u', sprintf('%.f', microtime(true))
        );
    }
}
