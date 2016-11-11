<?php
namespace Neos\Cqrs\Command;

/*
 * This file is part of the Neos.EventStore.DatabaseStorageAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Projection\ProjectionManager;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Package\PackageManagerInterface;

/**
 * CLI Command Controller for projection related commands
 *
 * @Flow\Scope("singleton")
 */
class ProjectionCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var ProjectionManager
     */
    protected $projectionManager;

    /**
     * @Flow\Inject
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @var array in the format ['<shortIdentifier>' => '<fullIdentifier>', ...]
     */
    private $shortProjectionIdentifiers;

    /**
     * List all projections
     *
     * This command displays a list of all projections and their respective short projection identifier which can
     * be used in the other projection commands.
     *
     * @return void
     */
    public function listCommand()
    {
        $lastPackageKey = null;
        foreach ($this->projectionManager->getProjections() as $projection) {
            $packageKey = $this->packageManager->getPackageByClassName($projection->getProjectorClassName())->getPackageKey();
            if ($packageKey !== $lastPackageKey) {
                $lastPackageKey = $packageKey;
                $this->outputLine();
                $this->outputLine('PACKAGE "%s":', array(strtoupper($packageKey)));
                $this->outputLine(str_repeat('-', $this->output->getMaximumLineLength()));
            }
            $this->outputLine('%-2s%-40s %s', array('', $this->getShortProjectionIdentifier($projection->getIdentifier()), $this->shortenText($projection->getProjectorClassName())));
        }
        $this->outputLine();
    }

    /**
     * Describe a projection
     *
     * This command displays detailed information about a specific projection, including the projector class name
     * and the event types which are processed by this projector.
     *
     * @param string $projection The projection identifier; see projection:list for possible options
     * @return void
     * @see neos.cqrs:projection:list
     */
    public function describeCommand($projection)
    {
        try {
            $projection = $this->projectionManager->getProjection($projection);
        } catch (\InvalidArgumentException $e) {
            $this->outputLine('<error>%s</error>', [$e->getMessage()]);
            $this->quit(1);
        }

        $this->outputLine('<b>PROJECTION:</b>');
        $this->outputLine('  <i>%s</i>', [$projection->getIdentifier()]);
        $this->outputLine();
        $this->outputLine('<b>REPLAY:</b>');
        $this->outputLine('  %s projection:replay %s', [$this->getFlowInvocationString(), $this->getShortProjectionIdentifier($projection->getIdentifier())]);
        $this->outputLine();
        $this->outputLine('<b>PROJECTOR:</b>');
        $this->outputLine('  %s', [$projection->getProjectorClassName()]);
        $this->outputLine();

        $this->outputLine('<b>HANDLED EVENT TYPES:</b>');
        foreach ($projection->getEventTypes() as $eventType) {
            $this->outputLine('  * %s', [$eventType]);
        }
    }

    /**
     * Replay a projection
     *
     * This command allows you to replay all relevant events for one specific projection.
     *
     * @param string $projection The projection identifier; see projection:list for possible options
     * @return void
     * @see neos.cqrs:projection:list
     * @see neos.cqrs:projection:replayall
     */
    public function replayCommand($projection)
    {
        try {
            $this->outputLine('Replaying events for projection "%s" ...', [$projection]);
            $eventsCount = $this->projectionManager->replay($projection);
            $this->outputLine('Replayed %s events.', [$eventsCount]);
        } catch (\Exception $e) {
            $this->outputLine('<error>%s</error>', [$e->getMessage()]);
            $this->quit(1);
        }
    }

    /**
     * Replay all projections
     *
     * This command allows you to replay all relevant events for all known projections.
     *
     * @param bool $onlyEmpty If specified, only projections which are currently empty will be considered
     * @return void
     * @see neos.cqrs:projection:replay
     * @see neos.cqrs:projection:list
     */
    public function replayAllCommand($onlyEmpty = false)
    {
        $eventsCount = 0;
        try {
            foreach ($this->projectionManager->getProjections() as $projection) {
                if ($onlyEmpty && !$this->projectionManager->isProjectionEmpty($projection->getIdentifier())) {
                    $this->outputLine('Skipping non-empty projection "%s" ...', [$projection->getIdentifier()]);
                } else {
                    $this->outputLine('Replaying events for projection "%s" ...', [$projection->getIdentifier()]);
                    $eventsCount += $this->projectionManager->replay($projection->getIdentifier());
                }
            }
            $this->outputLine('Replayed %s events.', [$eventsCount]);
        } catch (\Exception $e) {
            $this->outputLine('<error>%s</error>', [$e->getMessage()]);
            $this->quit(1);
        }
    }

    /**
     * Returns the shortest unambiguous projection identifier for a given $fullProjectionIdentifier
     *
     * @param string $fullProjectionIdentifier
     * @return string
     */
    private function getShortProjectionIdentifier(string $fullProjectionIdentifier)
    {
        if ($this->shortProjectionIdentifiers === null) {
            $projectionsByName = $projectionIdentifiers = [];
            foreach ($this->projectionManager->getProjections() as $projection) {
                $projectionIdentifiers[] = $projection->getIdentifier();
                list($packageKey, $projectionName) = explode(':', $projection->getIdentifier());
                if (!isset($projectionsByName[$projectionName])) {
                    $projectionsByName[$projectionName] = [];
                }
                $projectionsByName[$projectionName][] = $packageKey;
            }
            $this->shortProjectionIdentifiers = [];
            foreach ($projectionIdentifiers as $projectionIdentifier) {
                list($packageKey, $projectionName) = explode(':', $projectionIdentifier);
                if (count($projectionsByName[$projectionName]) === 1) {
                    $this->shortProjectionIdentifiers[$projectionIdentifier] = $projectionName;
                    continue;
                }
                $prefix = null;
                foreach (array_reverse(explode('.', $packageKey)) as $packageKeyPart) {
                    $prefix = $prefix === null ? $packageKeyPart : $packageKeyPart . '.' . $prefix;
                    $matchingPackageKeys = array_filter($projectionsByName[$projectionName], function ($searchedPackageKey) use ($packageKey) {
                        return $searchedPackageKey === $packageKey || substr($packageKey, -(strlen($searchedPackageKey) + 1)) === '.' . $searchedPackageKey;
                    });
                    if (count($matchingPackageKeys) === 1) {
                        $this->shortProjectionIdentifiers[$projectionIdentifier] = $prefix . ':' . $projectionName;
                        break;
                    }
                }
            }
        }
        return isset($this->shortProjectionIdentifiers[$fullProjectionIdentifier]) ? $this->shortProjectionIdentifiers[$fullProjectionIdentifier] : $fullProjectionIdentifier;
    }

    /**
     * Shortens the given text by removing characters from the middle
     *
     * @param string $text Text to shorten
     * @param int $maximumCharacters Maximum of characters
     * @return string
     */
    private function shortenText($text, $maximumCharacters = 36)
    {
        $length = strlen($text);
        if ($length <= $maximumCharacters) {
            return $text;
        }
        return substr_replace($text, '...', ($maximumCharacters - 3) / 2, $length - $maximumCharacters + 3);
    }
}
