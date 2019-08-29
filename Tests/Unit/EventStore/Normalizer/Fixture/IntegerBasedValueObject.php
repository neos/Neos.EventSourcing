<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Normalizer\Fixture;

class IntegerBasedValueObject
{
    public static function fromInteger(int $value): self
    {
        return new self();
    }
}
