<?php
declare(strict_types=1);
namespace Neos\EventSourcing;

use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\EventSourcing\Event\DomainEvents;

interface EventRecordingInterface
{
    public function recordThat(DomainEventInterface $event): void;

    public function pullUncommittedEvents(): DomainEvents;
}
