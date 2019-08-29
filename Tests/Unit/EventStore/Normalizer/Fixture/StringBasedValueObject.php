<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Normalizer\Fixture;

class StringBasedValueObject
{
    public static function fromString(string $value): self
    {
        return new self();
    }
}
