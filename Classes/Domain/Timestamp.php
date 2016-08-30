<?php
namespace Ttree\Cqrs\Domain;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

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
