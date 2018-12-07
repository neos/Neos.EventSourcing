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

use Neos\Error\Messages\Error;
use Neos\Error\Messages\Notice;
use Neos\Error\Messages\Result;
use Neos\Error\Messages\Warning;
use Neos\EventSourcing\EventStore\EventStoreManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Mvc\Exception\StopActionException;

/**
 * CLI Command Controller for storage related commands of the Neos Event Store
 *
 * @Flow\Scope("singleton")
 */
class EventStoreCommandController extends CommandController
{
    /**
     * @var EventStoreManager
     * @Flow\Inject
     */
    protected $eventStoreManager;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="EventStore.storage.options")
     */
    protected $configuration;

    /**
     * Sets up the specified Event Store backend
     *
     * This command initializes the given Event Store adapters (i.e. creates required tables if it is database
     * driven and/or validates the configuration against the actual backend)
     *
     * @param string $eventStore The identifier of the Event Store to set up
     * @return void
     * @throws StopActionException
     */
    public function setupCommand($eventStore): void
    {
        $eventStores = $this->eventStoreManager->getAllEventStores();
        if (!isset($eventStores[$eventStore])) {
            $this->outputLine('<error>There is no Event Store "%s" configured</error>', [$eventStore]);
            $this->quit(1);
        }
        $this->outputLine('Setting up Event Store "%s"', [$eventStore]);
        $result = $eventStores[$eventStore]->setup();
        $this->renderResult($result);
    }

    /**
     * Sets up all configured Event Store backends
     *
     * This command initializes all configured Event Store adapters (i.e. creates required tables for database
     * driven storages and/or validates the configuration against the actual backends)
     *
     * @return void
     */
    public function setupAllCommand(): void
    {
        $eventStores = $this->eventStoreManager->getAllEventStores();
        $this->outputLine('Setting up <b>%d</b> Event Store backend(s):', [count($eventStores)]);
        foreach ($eventStores as $eventStoreIdentifier => $eventStore) {
            $this->outputLine();
            $this->outputLine('<b>Event Store "%s":</b>', [$eventStoreIdentifier]);
            $this->outputLine(str_repeat('-', $this->output->getMaximumLineLength()));
            $result = $eventStore->setup();
            $this->renderResult($result);
        }
    }

    /**
     * Display Event Store connection status
     *
     * This command displays some basic status about the connection of the configured Event Stores.
     *
     * @return void
     */
    public function statusCommand(): void
    {
        $eventStores = $this->eventStoreManager->getAllEventStores();
        $this->outputLine('Displaying status information for <b>%d</b> Event Store backend(s):', [count($eventStores)]);

        foreach ($eventStores as $eventStoreIdentifier => $eventStore) {
            $this->outputLine();
            $this->outputLine('<b>Event Store "%s"</b>', [$eventStoreIdentifier]);
            $this->outputLine(str_repeat('-', $this->output->getMaximumLineLength()));

            $this->renderResult($eventStore->getStatus());
        }
    }

    /**
     * Outputs the given Result object in a human-readable way
     *
     * @param Result $result
     */
    private function renderResult(Result $result): void
    {
        if ($result->hasNotices()) {
            /** @var Notice $notice */
            foreach ($result->getNotices() as $notice) {
                if ($notice->getTitle() !== null) {
                    $this->outputLine('<b>%s</b>: %s', [$notice->getTitle(), $notice->render()]);
                } else {
                    $this->outputLine($notice->render());
                }
            }
        }

        if ($result->hasErrors()) {
            /** @var Error $error */
            foreach ($result->getErrors() as $error) {
                $this->outputLine('<error>ERROR: %s</error>', [$error->render()]);
            }
        } elseif ($result->hasWarnings()) {
            /** @var Warning $warning */
            foreach ($result->getWarnings() as $warning) {
                if ($warning->getTitle() !== null) {
                    $this->outputLine('<b>%s</b>: <em>%s !!!</em>', [$warning->getTitle(), $warning->render()]);
                } else {
                    $this->outputLine('<em>%s !!!</em>', [$warning->render()]);
                }
            }
        } else {
            $this->outputLine('<success>SUCCESS</success>');
        }
    }
}
