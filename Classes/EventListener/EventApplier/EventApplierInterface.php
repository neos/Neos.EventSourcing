<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventListener\EventApplier;

use Neos\EventSourcing\EventListener\Exception\EventCouldNotBeAppliedException;
use Neos\EventSourcing\EventStore\EventEnvelope;

interface EventApplierInterface
{
    /**
     * @param EventEnvelope $eventEnvelope
     * @throws EventCouldNotBeAppliedException
     */
    public function __invoke(EventEnvelope $eventEnvelope): void;
}
