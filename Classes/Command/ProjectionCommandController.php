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
     * @return void
     */
    public function listCommand()
    {
        $lastPackageKey = null;
        foreach ($this->projectionManager->getProjections() as $projection) {
            if ($projection->getPackageKey() !== $lastPackageKey) {
                $lastPackageKey = $projection->getPackageKey();
                $this->outputLine();
                $this->outputLine('PACKAGE "%s":', array(strtoupper($projection->getPackageKey())));
                $this->outputLine(str_repeat('-', $this->output->getMaximumLineLength()));
            }
            $this->outputLine('%-2s%-40s %s', array('', $projection->getFullIdentifier(), 'foo'));
        }
        $this->outputLine();
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
