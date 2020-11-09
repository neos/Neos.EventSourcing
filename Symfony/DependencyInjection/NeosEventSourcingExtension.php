<?php


namespace Neos\EventSourcing\Symfony\DependencyInjection;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class NeosEventSourcingExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__, '/../Resources/config')
        );
        $loader->load('services.yaml');
    }

    public function getAlias()
    {
        return 'neos_eventsourcing';
    }
}