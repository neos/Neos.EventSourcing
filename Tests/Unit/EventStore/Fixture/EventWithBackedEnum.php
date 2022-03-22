<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Fixture;

use Neos\EventSourcing\Event\DomainEventInterface;

final class EventWithBackedEnum implements DomainEventInterface
{
    /**
     * @var BackedEnum
     */
    private $enum;

    public function __construct(BackedEnum $enum)
    {
        $this->enum = $enum;
    }

    public function getEnum(): BackedEnum
    {
        return $this->enum;
    }

}
