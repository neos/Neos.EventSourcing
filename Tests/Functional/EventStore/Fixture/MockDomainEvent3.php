<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Functional\EventStore\Fixture;

use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
final class MockDomainEvent3 implements DomainEventInterface
{
    /**
     * @var string
     */
    private $value;

    public function __construct(MockValueObject $value)
    {
        $this->value = $value;
    }

    public function getValue(): MockValueObject
    {
        return $this->value;
    }

}
