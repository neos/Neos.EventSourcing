<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Normalizer\Fixture;

class BooleanBasedValueObject
{
    public static function fromBoolean(bool $value): self
    {
        return new self();
    }
}
