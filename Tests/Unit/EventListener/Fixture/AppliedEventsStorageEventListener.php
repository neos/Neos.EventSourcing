<?php
declare(strict_types=1);

namespace Neos\EventSourcing\Tests\Unit\EventListener\Fixture;

use Neos\EventSourcing\EventListener\AppliedEventsStorage\AppliedEventsStorageInterface;
use Neos\EventSourcing\EventListener\EventListenerInterface;
use Neos\EventSourcing\EventListener\ProvidesAppliedEventsStorageInterface;

class AppliedEventsStorageEventListener implements EventListenerInterface, ProvidesAppliedEventsStorageInterface
{

    /**
     * @var AppliedEventsStorageInterface
     */
    private $appliedEventsStorage;

    public function __construct(AppliedEventsStorageInterface $appliedEventsStorage)
    {
        $this->appliedEventsStorage = $appliedEventsStorage;
    }

    public function getAppliedEventsStorage(): AppliedEventsStorageInterface
    {
        return $this->appliedEventsStorage;
    }
}
