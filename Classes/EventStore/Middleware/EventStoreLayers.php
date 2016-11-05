<?php
namespace Neos\Cqrs\EventStore\Middleware;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\EventStore\EventStoreCommit;
use Neos\Cqrs\Middleware\MiddlewareLayers;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * @Flow\Scope("singleton")
 */
class EventStoreLayers
{
    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $middelwares = [];

    /**
     * @param EventStoreCommit $commit
     * @param \Closure $core
     */
    public function execute(EventStoreCommit $commit, \Closure $core)
    {
        if ($this->middelwares === []) {
            $this->middelwares = array_map(function ($className) {
                return new $className;
            }, self::detectLayers($this->objectManager));
        }

        $middleware = new MiddlewareLayers($this->middelwares);
        $middleware->execute($commit, $core);
    }

    /**
     * Detects and collects all existing event bus middleware layers
     *
     * @param ObjectManagerInterface $objectManager
     * @return array
     * @Flow\CompileStatic
     */
    protected static function detectLayers(ObjectManagerInterface $objectManager): array
    {
        /** @var ReflectionService $reflectionService */
        $reflectionService = $objectManager->get(ReflectionService::class);
        return $reflectionService->getAllImplementationClassNamesForInterface(EventStoreLayerInterface::class);
    }
}
