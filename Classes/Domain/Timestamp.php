<?php
namespace Flowpack\Cqrs\Domain;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * Timestamp
 */
class Timestamp
{
    const OUTPUT_FORMAT = 'Y-m-d\TH:i:s.uO';

    /**
     * @return \DateTime
     */
    public static function create()
    {
        return \DateTime::createFromFormat(
            'U.u', sprintf('%.f', microtime(true))
        );
    }
}
