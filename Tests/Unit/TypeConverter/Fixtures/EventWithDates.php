<?php
namespace Neos\EventSourcing\Tests\Unit\TypeConverter\Fixtures;

use Neos\EventSourcing\Event\EventInterface;

final class EventWithDates implements EventInterface
{
    /**
     * @var \DateTime
     */
    private $someDateTime;

    /**
     * @var \DateTimeImmutable
     */
    private $someImmutableDateTime;

    /**
     * @var \DateTimeZone
     */
    private $someDateTimeZone;

    /**
     * @param \DateTime $someDateTime
     * @param \DateTimeImmutable $someImmutableDateTime
     * @param \DateTimeZone $someDateTimeZone
     */
    public function __construct(\DateTime $someDateTime, \DateTimeImmutable $someImmutableDateTime, \DateTimeZone $someDateTimeZone)
    {
        $this->someDateTime = $someDateTime;
        $this->someImmutableDateTime = $someImmutableDateTime;
        $this->someDateTimeZone = $someDateTimeZone;
    }

    public function getSomeDateTime(): \DateTime
    {
        return $this->someDateTime;
    }

    public function getSomeImmutableDateTime(): \DateTimeImmutable
    {
        return $this->someImmutableDateTime;
    }

    public function getSomeDateTimeZone(): \DateTimeZone
    {
        return $this->someDateTimeZone;
    }

}