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

use Neos\EventSourcing\EventPublisher\Mapping\DefaultMappingProvider;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class DefaultEventPublisherFactory implements EventPublisherFactoryInterface
{

    /**
     * @var \Neos\EventSourcing\EventPublisher\Mapping\DefaultMappingProvider
     */
    private $mappingProvider;

    public function __construct(DefaultMappingProvider $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

    public function create(string $eventStoreIdentifier): EventPublisherInterface
    {
        $mappings = $this->mappingProvider->getMappingsForEventStore($eventStoreIdentifier);
        return DeferEventPublisher::forPublisher(new JobQueueEventPublisher($eventStoreIdentifier, $mappings));
    }
}
