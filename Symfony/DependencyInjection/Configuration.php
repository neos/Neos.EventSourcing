<?php

namespace Neos\EventSourcing\Symfony\DependencyInjection;

use Doctrine\ORM\EntityManager;
use ReflectionClass;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Exception\LogicException;

use function array_key_exists;
use function assert;
use function in_array;
use function is_array;


class Configuration implements ConfigurationInterface
{
    public function __construct()
    {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('neos_eventsourcing');
        $rootNode    = $treeBuilder->getRootNode();

        $this->addDbalSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Add DBAL section to configuration tree
     */
    private function addDbalSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('stores')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('eventTableName')->end()
                            ->arrayNode('listenerClassNames')
                                ->scalarPrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
