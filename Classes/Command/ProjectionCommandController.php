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
            $this->outputLine('%-2s%-40s %s', array('', $projection->getShortIdentifier(), $this->shortenText($projection->getProjectorClassName())));
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
            $this->outputLine('<b>PROJECTION:</b>');
            $this->outputLine('  <i>%s</i>', [$projection->getFullIdentifier()]);
            $this->outputLine();
            $this->outputLine('<b>REPLAY:</b>');
            $this->outputLine('  %s projection:replay %s', [$this->getFlowInvocationString(), $projection->getShortIdentifier()]);
            $this->outputLine();
            $this->outputLine('<b>PROJECTOR:</b>');
            $this->outputLine('  %s', [$projection->getProjectorClassName()]);
            $this->outputLine();

            $this->outputLine('<b>HANDLED EVENT TYPES:</b>');
            foreach ($projection->getEventTypes() as $eventType) {
                $this->outputLine('  * %s', [$eventType]);
            }
        } catch (\Exception $e) {
            $this->outputLine('<error>%s</error>', [$e->getMessage()]);
            $this->quit(1);
        }
    }

    /**
     * Replay projections
     *
     * This command allows you to replay all relevant events for a given projection.
     *
     * @param string $projection The projection identifier; see projection:list for possible options
     * @return void
     * @see neos.cqrs:projection:list
     */
    public function replayCommand($projection)
    {
        $this->projectionManager->replay($projection);
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
