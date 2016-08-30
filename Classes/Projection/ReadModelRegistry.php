<?php
namespace Ttree\Cqrs\Projection;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * ReadModelRegistry
 *
 * @Flow\Scope("singleton")
 */
class ReadModelRegistry
{
    /**
     * @var \Closure[]
     */
    protected $persistenceQueue = [];

    /**
     * @var object[]
     */
    protected $runtimeCache = [];

    /**
     * @param string $hash
     * @param \Closure $callback
     */
    public function persist(string $hash, \Closure $callback)
    {
        $this->persistenceQueue[$hash] = $callback;
    }

    /**
     * @param string $hash
     * @param string $object
     */
    public function set($hash, $object)
    {
        $this->runtimeCache[$hash] = $object;
    }

    /**
     * @param string $hash
     * @return object
     */
    public function get(string $hash)
    {
        return isset($this->runtimeCache[$hash]) ? $this->runtimeCache[$hash] : null;
    }

    /**
     * @param string $hash
     * @return boolean
     */
    public function has(string $hash): bool
    {
        return isset($this->runtimeCache[$hash]);
    }

    /**
     * @param string $hash
     */
    public function remove(string $hash)
    {
        unset($this->runtimeCache[$hash]);
    }

    /**
     * @return void
     */
    public function flush()
    {
        array_map(function (\Closure $closure) {
            $closure();
        }, $this->persistenceQueue);
    }
}
