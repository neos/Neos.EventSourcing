<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Functional\EventStore\Fixture;

use Neos\EventSourcing\Event\DomainEventInterface;

final class MockDomainEvent implements DomainEventInterface
{
    /**
     * @var string
     */
    private $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function getString(): string
    {
        return $this->string;
    }

}
