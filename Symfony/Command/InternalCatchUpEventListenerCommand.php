<?php

declare(strict_types=1);

namespace Neos\EventSourcing\Symfony\Command;

use App\Domain\Command\SayHello;
use Doctrine\DBAL\Connection;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Notice;
use Neos\Error\Messages\Result;
use Neos\Error\Messages\Warning;
use Neos\EventSourcing\EventListener\EventListenerInvoker;
use Neos\EventSourcing\EventStore\EventStore;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InternalCatchUpEventListenerCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'eventsourcing:internal:catchup-event-listener';


    /**
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * SayHelloCommand constructor.
     * @param EventStore $eventStore
     */
    public function __construct(EventStore $eventStore, ContainerInterface $container, Connection $connection)
    {
        $this->eventStore = $eventStore; // TODO multiple event stores
        $this->container = $container;
        $this->connection = $connection;
        parent::__construct();
    }


    protected function configure()
    {
        $this->addArgument('eventListenerClassName', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        dump("HALLO");
        $eventListenerClassName = $input->getArgument('eventListenerClassName');

        dump($eventListenerClassName);
        $listener = $this->container->get($eventListenerClassName);

        dump("HI");
        dump($listener);
        $eventListenerInvoker = new EventListenerInvoker($this->eventStore, $listener, $this->connection);
        $eventListenerInvoker->catchUp();

        die();
        return Command::SUCCESS;
    }

}