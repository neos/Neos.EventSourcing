<?php
declare(strict_types=1);
namespace Neos\EventSourcing\EventPublisher;

/*
 * This file is part of the Neos.EventStore package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\EventListener\Mapping\DefaultEventToListenerMappingProvider;
use Neos\Flow\Annotations as Flow;

/**
 * The default Event Publisher factory that is used within Flow if no other "eventPublisherFactory" is specified in the corresponding Event Store configuration.
 *
 * @Flow\Scope("singleton")
 */
final class DefaultEventPublisherFactory implements EventPublisherFactoryInterface
{

    /**
     * @var DefaultEventToListenerMappingProvider
     */
    private $mappingProvider;

    public function __construct(DefaultEventToListenerMappingProvider $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

    public function create(string $eventStoreIdentifier): EventPublisherInterface
    {
        $mappings = $this->mappingProvider->getMappingsForEventStore($eventStoreIdentifier);
        return DeferEventPublisher::forPublisher(new JobQueueEventPublisher($eventStoreIdentifier, $mappings));
    }
}
