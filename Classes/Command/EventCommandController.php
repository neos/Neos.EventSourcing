<?php
namespace Neos\EventSourcing\Command;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\EventListener\AppliedEventsLogRepository;
use Neos\EventSourcing\EventListener\EventListenerManager;
use Neos\EventSourcing\EventStore\EventAndRawEvent;
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\EventSourcing\EventStore\EventStream;
use Neos\EventSourcing\EventStore\EventStreamFilterInterface;
use Neos\EventSourcing\EventStore\EventTypesFilter;
use Neos\EventSourcing\EventStore\Exception\EventStreamNotFoundException;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Core\Booting\Scripts;

/**
 * CLI Command Controller for event related commands
 *
 * @Flow\Scope("singleton")
 */
class EventCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var EventListenerManager
     */
    protected $eventListenerManager;

    /**
     * @Flow\Inject
     * @var EventStoreManager
     */
    protected $eventStoreManager;

    /**
     * @Flow\Inject
     * @var AppliedEventsLogRepository
     */
    protected $appliedEventsLogRepository;

    /**
     * @Flow\InjectConfiguration(package="Neos.Flow")
     * @var array
     */
    protected $flowSettings;

    /**
     * Forward new events to listeners
     *
     * This command allows you to play all relevant unseen events for all asynchronous event listeners.
     *
     * @param bool $verbose If specified, this command will display information about the events being applied
     * @param bool $quiet If specified, this command won't produce any output apart from errors
     * @return void
     * @see neos.eventsourcing:event:watch
     */
    public function catchUpCommand($verbose = false, $quiet = false)
    {
        $eventsCount = 0;
        $progressCallback = function (RawEvent $rawEvent) use ($quiet, $verbose, &$eventsCount) {
            $eventsCount += 1;
            $this->outputIfVerbose(sprintf('  %s (%d)', $rawEvent->getType(), $rawEvent->getSequenceNumber()), '*');
        };
        foreach ($this->eventListenerManager->getEventListenerIdentifiers() as $eventListenerIdentifier) {

            $lastAppliedSequenceNumber = $this->appliedEventsLogRepository->reserveHighestAppliedSequenceNumber($eventListenerIdentifier);

            $eventTypes = $this->eventListenerManager->getEventTypesByListener($eventListenerIdentifier);
            $eventStreamFilter = new EventTypesFilter($eventTypes, $lastAppliedSequenceNumber + 1);

            $this->outputIfVerbose(sprintf('Applying events for <b>%s</b> from sequence number <b>%d</b>:', $eventListenerIdentifier, $eventStreamFilter->getFilterValue(EventStreamFilterInterface::FILTER_MINIMUM_SEQUENCE_NUMBER)));

            $eventStore = $this->eventStoreManager->getEventStoreForEventListener($this->eventListenerManager->getEventListenerClassName($eventListenerIdentifier));
            try {
                $eventStream = $eventStore->get($eventStreamFilter);
            } catch (EventStreamNotFoundException $exception) {
                $this->outputIfVerbose('No (new) events found...');
                $this->appliedEventsLogRepository->releaseHighestAppliedSequenceNumber();
                continue;
            }

            // TODO is this rewind required?
            $eventStream->rewind();

            while (true) {
                $eventAndRawEvent = $eventStream->current();
                $rawEvent = $eventAndRawEvent->getRawEvent();
                try {
                    $this->eventListenerManager->invokeListener($eventListenerIdentifier, $eventAndRawEvent->getEvent(), $rawEvent);
                } catch (\Exception $exception) {
                    $this->appliedEventsLogRepository->releaseHighestAppliedSequenceNumber();
                    throw $exception;
                }
                $this->outputIfVerbose(sprintf('  %s (%d)', $rawEvent->getType(), $rawEvent->getSequenceNumber()), '*');
                $this->appliedEventsLogRepository->saveHighestAppliedSequenceNumber($eventListenerIdentifier, $rawEvent->getSequenceNumber());

                $eventStream->next();
                if (!$eventStream->valid()) {
                    // no more events
                    continue 2;
                }

                $lastAppliedSequenceNumber = $this->appliedEventsLogRepository->reserveHighestAppliedSequenceNumber($eventListenerIdentifier);
                if ($lastAppliedSequenceNumber !== $rawEvent->getSequenceNumber()) {
                    $this->appliedEventsLogRepository->releaseHighestAppliedSequenceNumber();
                    // HSN has been updated in the meantime => cancel.
                    $this->outputIfVerbose(sprintf('HighestAppliedSequenceNumber jumped from %d to %d - aborting', $rawEvent->getSequenceNumber(), $lastAppliedSequenceNumber));
                    continue 2;
                }
            }

            $this->outputIfVerbose('');
        }
        $this->outputIfVerbose(sprintf('Applied %d events.', $eventsCount));
    }

    /**
     * Listen to new events
     *
     * This command watches the event store for new events and applies them to the respective asynchronous event
     * listeners. These include projectors, process managers and custom event listeners implementing the relevant
     * interfaces.
     *
     * @param int $lookupInterval Pause between lookups (in seconds)
     * @param bool $verbose If specified, this command will display information about the events being applied
     * @param bool $quiet If specified, this command won't produce any output apart from errors (useful for automation)
     * @return void
     * @see neos.eventsourcing:event:catchup
     */
    public function watchCommand($lookupInterval = 10, $verbose = false, $quiet = false)
    {
        if ($verbose) {
            $this->outputLine('Watching events ...');
        }

        do {
            $catchupCommandArguments = [
                'quiet' => $quiet ? 'yes' : 'no',
                'verbose' => $verbose ? 'yes' : 'no'
            ];
            Scripts::executeCommand('neos.eventsourcing:event:catchup', $this->flowSettings, !$quiet, $catchupCommandArguments);
            $this->outputIfVerbose('', '.');
            sleep($lookupInterval);
        } while (true);
    }

    /**
     * A "conditional" outputLine implementation, respecting the "quiet" and "verbose" CLI arguments
     *
     * @param string $verboseText The string that is rendered if the "verbose" flag is set
     * @param string|null $shortText The (optional) string that is rendered otherwise
     *
     */
    private function outputIfVerbose(string $verboseText, string $shortText = null)
    {
        if ($this->request->hasArgument('quiet') && $this->request->getArgument('quiet') === true) {
            return;
        }
        if ($this->request->hasArgument('verbose') && $this->request->getArgument('verbose') === true) {
            $this->outputLine($verboseText);
        } elseif ($shortText !== null) {
            $this->output($shortText);
        }
    }
}
