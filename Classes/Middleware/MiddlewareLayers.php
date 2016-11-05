<?php
namespace Neos\Cqrs\Middleware;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cqrs\Exception\InvalidArgumentException;
use TYPO3\Flow\Annotations as Flow;

/**
 * MiddlewareLayers
 */
class MiddlewareLayers
{
    /**
     * @var array
     */
    protected $layers = [];

    /**
     * @param array $layers
     */
    public function __construct(array $layers = [])
    {
        $this->layers = $layers;
    }

    /**
     * @param mixed $layers
     * @return MiddlewareLayers
     * @throws \Neos\Cqrs\Exception\InvalidArgumentException
     */
    public function register($layers)
    {
        if ($layers instanceof MiddlewareLayers) {
            $layers = $layers->toArray();
        }
        if ($layers instanceof LayerInterface) {
            $layers = [$layers];
        }
        if (!is_array($layers)) {
            throw new InvalidArgumentException(get_class($layers) . ' is not a valid onion layer.');
        }
        return new static(array_merge($this->layers, $layers));
    }

    /**
     * @param mixed $object
     * @param \Closure $core
     * @return mixed
     */
    public function execute($object, \Closure $core)
    {
        $coreFunction = $this->createCoreFunction($core);
        $layers = array_reverse($this->layers);
        $completeOnion = array_reduce($layers, function ($nextLayer, $layer) {
            return $this->createLayer($nextLayer, $layer);
        }, $coreFunction);
        return $completeOnion($object);
    }

    /**
     * Get the layers of this onion, can be used to merge with another onion
     * @return array
     */
    public function toArray()
    {
        return $this->layers;
    }

    /**
     * @param \Closure $core the core function
     * @return \Closure
     */
    protected function createCoreFunction(\Closure $core)
    {
        return function ($object) use ($core) {
            return call_user_func($core, $object);
        };
    }

    /**
     * @param LayerInterface $nextLayer
     * @param LayerInterface $layer
     * @return \Closure
     */
    protected function createLayer($nextLayer, $layer)
    {
        return function ($object) use ($nextLayer, $layer) {
            return call_user_func_array([$layer, 'execute'], [$object, $nextLayer]);
        };
    }
}
