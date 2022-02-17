<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventStore\Fixture;

use Neos\EventSourcing\Event\DomainEventInterface;

final class EventWithDateTime implements DomainEventInterface
{
    /**
     * @var \DateTimeInterface
     */
    private $date;

    public function __construct(\DateTimeInterface $date)
    {
        $this->date = $date;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function equals(self $other): bool
    {
        return $other->date->getTimestamp() === $this->date->getTimestamp();
    }

}
