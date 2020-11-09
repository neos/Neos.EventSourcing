<?php


namespace Neos\EventSourcing\Symfony\DependencyInjection;


use Doctrine\DBAL\Connection;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\EventStore\Storage\Doctrine\DoctrineEventStorage;
use Neos\EventSourcing\Symfony\EventPublisher\SymfonyEventPublisher;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class NeosEventSourcingExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        dump($config);


        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');

        foreach ($config['stores'] as $name => $store) {
            $container->register('neos_eventsourcing.eventstore.' . $name)
                ->setClass(EventStore::class)
                ->setPublic(true)
                ->setArgument('$storage', new Reference('neos_eventsourcing.eventstore.' . $name . '.storage'))
                ->setArgument('$eventPublisher', new Reference('neos_eventsourcing.eventstore.' . $name . '.publisher'))
                ->setArgument('$eventNormalizer', new Reference('neos_eventsourcing_eventStore_eventNormalizer'));

            $container->register('neos_eventsourcing.eventstore.' . $name . '.storage')
                ->setClass(DoctrineEventStorage::class) // TODO make configurable
                ->setArgument('$options', ['eventTableName' => $store['eventTableName']])
                ->setArgument('$eventNormalizer', new Reference('neos_eventsourcing_eventStore_eventNormalizer'))
                ->setArgument('$connection', new Reference(Connection::class));

            $container->register('neos_eventsourcing.eventstore.' . $name . '.publisher')
                ->setClass(SymfonyEventPublisher::class)
                ->setArgument('$eventDispatcher', new Reference(EventDispatcherInterface::class))
                ->setArgument('$eventStoreContainerId', 'neos_eventsourcing.eventstore.' . $name);

        }
    }

    public function getAlias()
    {
        return 'neos_eventsourcing';
    }
}