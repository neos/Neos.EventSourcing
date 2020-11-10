<?php
declare(strict_types=1);
namespace Neos\EventSourcing\Projection;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Neos\EventSourcing\EventListener\EventListenerInvoker;
use Neos\EventSourcing\EventListener\Exception\EventCouldNotBeAppliedException;
use Neos\EventSourcing\EventListener\Mapping\DefaultEventToListenerMappingProvider;
use Neos\EventSourcing\EventStore\EventStoreFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ClassReflection;
use Neos\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException;
use Neos\Flow\Reflection\ReflectionService;

interface ProjectionManagerInterface
{
    /**
     * Return all detected projections
     *
     * @return Projection[]
     * @api
     */
    public function getProjections(): array;

    /**
     * Returns information about a specific projection in form of a Projection DTO
     *
     * @param string $projectionIdentifier The short or full projection identifier
     * @return Projection
     * @api
     */
    public function getProjection(string $projectionIdentifier): Projection;

    /**
     * Replay events of the specified projection
     *
     * @param string $projectionIdentifier unambiguous identifier of the projection to replay
     * @param Closure|null $progressCallback If set, this callback is invoked for every applied event during replay with the arguments $sequenceNumber and $eventStreamVersion
     * @return void
     * @throws EventCouldNotBeAppliedException
     * @api
     */
    public function replay(string $projectionIdentifier, Closure $progressCallback = null): void;

    /**
     * Replay events of the specified projection until the specified event sequence number
     *
     * @param string $projectionIdentifier unambiguous identifier of the projection to replay
     * @param int $maximumSequenceNumber The sequence number of the event until which events should be replayed. The specified event will be included in the replay.
     * @param Closure|null $progressCallback If set, this callback is invoked for every applied event during replay with the arguments $sequenceNumber and $eventStreamVersion
     * @throws EventCouldNotBeAppliedException
     */
    public function replayUntilSequenceNumber(string $projectionIdentifier, int $maximumSequenceNumber, Closure $progressCallback = null): void;

    /**
     * Catch up on events for the specified projection
     *
     * @param string $projectionIdentifier unambiguous identifier of the projection to catch up for
     * @param Closure|null $progressCallback If set, this callback is invoked for every applied event during catch-up with the arguments $sequenceNumber and $eventStreamVersion
     * @throws EventCouldNotBeAppliedException
     */
    public function catchUp(string $projectionIdentifier, Closure $progressCallback = null): void;

    /**
     * Catch up on events for the specified projection up to the specified event
     *
     * @param string $projectionIdentifier unambiguous identifier of the projection to catch up for
     * @param int $maximumSequenceNumber The sequence number of the event until which events should be applied. The specified event will be included in the replay.
      * @param Closure|null $progressCallback If set, this callback is invoked for every applied event during catch-up with the arguments $sequenceNumber and $eventStreamVersion
     * @throws EventCouldNotBeAppliedException
     */
    public function catchUpUntilSequenceNumber(string $projectionIdentifier, int $maximumSequenceNumber, Closure $progressCallback = null): void;
}
