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
            $this->outputLine('%-2s%-40s %s', array('', $projection->getShortIdentifier(), $this->truncate($projection->getProjectorClassName())));
        }
        $this->outputLine();
    }

    /**
     * @param string $projection
     * @return void
     */
    public function describeCommand($projection)
    {
        $projection = $this->projectionManager->getProjection($projection);
        $this->outputLine('<b>PROJECTION:</b>');
        $this->outputLine('  <i>%s</i>', [$projection->getFullIdentifier()]);
        $this->outputLine();
        $this->outputLine('<b>REPLAY:</b>');
        $this->outputLine('  %s projetion:replay %s', [$this->getFlowInvocationString(), $projection->getShortIdentifier()]);
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
     * @param string $text
     * @param int $maximumCharacters
     * @return mixed
     */
    private function truncate($text, $maximumCharacters = 36)
    {
        $length = strlen($text);
        if ($length <= $maximumCharacters) {
            return $text;
        }
        return substr_replace($text, '...', ($maximumCharacters - 3) / 2, $length - $maximumCharacters + 3);
    }

    /**
     * @param string $identifier
     * @return void
     */
    public function replayCommand($identifier)
    {
        $this->projectionManager->replay($identifier);
    }
}
