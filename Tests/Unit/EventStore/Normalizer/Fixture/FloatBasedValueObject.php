<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Normalizer\Fixture;

class FloatBasedValueObject
{
    public static function fromFloat(float $value): self
    {
        return new self();
    }
}
