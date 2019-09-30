<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Normalizer\Fixture;

/**
 * see https://github.com/neos/Neos.EventSourcing/issues/233
 */
class InvalidArrayBasedValueObject
{
    public function __construct(array $value)
    {
    }
}
