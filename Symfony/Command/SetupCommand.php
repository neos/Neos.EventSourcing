<?php

declare(strict_types=1);

namespace Neos\EventSourcing\Symfony\Command;

use Neos\Error\Messages\Error;
use Neos\Error\Messages\Notice;
use Neos\Error\Messages\Result;
use Neos\Error\Messages\Warning;
use Neos\EventSourcing\EventStore\EventStore;
use Neos\EventSourcing\Symfony\EventListener\AppliedEventsStorage\DoctrineAppliedEventsStorageSetup;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupCommand extends Command
{
    protected static $defaultName = 'eventsourcing:store-setup';

    /**
     * @var DoctrineAppliedEventsStorageSetup
     */
    private $doctrineAppliedEventsStorageSetup;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(
        DoctrineAppliedEventsStorageSetup $doctrineAppliedEventsStorageSetup,
        ContainerInterface $container
    )
    {
        $this->doctrineAppliedEventsStorageSetup = $doctrineAppliedEventsStorageSetup;
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Creates the needed stores.')
            ->setHelp('This command allows you to create the needed stores which are defined in the config.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->container->getParameter('neos_eventsourcing');

        $result = new Result();
        foreach ($config['stores'] as $name => $store) {
            $store = $this->container->get('neos_eventsourcing.eventstore.' . $name);
            /* @var $store EventStore */
            $result->merge($store->setup());
        }

        $result->merge($this->doctrineAppliedEventsStorageSetup->setup());
        self::renderResult($result, $output);

        if ($result->hasErrors()) {
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

    /**
     * Outputs the given Result object in a human-readable way
     *
     * @param Result $result
     */
    private static function renderResult(Result $result, OutputInterface $output): void
    {
        if ($result->hasNotices()) {
            /** @var Notice $notice */
            foreach ($result->getNotices() as $notice) {
                if ($notice->getTitle() !== null) {
                    $output->writeln(
                        vsprintf(
                            '<b>%s</b>: %s',
                            [
                                $notice->getTitle(),
                                $notice->render()
                            ]
                        )
                    );
                } else {
                    $output->writeln($notice->render());
                }
            }
        }

        if ($result->hasErrors()) {
            /** @var Error $error */
            foreach ($result->getErrors() as $error) {
                $output->writeln(
                    vsprintf(
                        '<error>ERROR: %s</error>',
                        [
                            $error->render()
                        ]
                    )
                );
            }
        } elseif ($result->hasWarnings()) {
            /** @var Warning $warning */
            foreach ($result->getWarnings() as $warning) {
                if ($warning->getTitle() !== null) {
                    $output->writeln(
                        vsprintf(
                            '<b>%s</b>: <em>%s !!!</em>',
                            [
                                $warning->getTitle(),
                                $warning->render()
                            ]
                        )
                    );
                } else {
                    $output->writeln(
                        vsprintf(
                            '<em>%s !!!</em>',
                            [
                                $warning->render()
                            ]
                        )
                    );
                }
            }
        } else {
            $output->writeln('<info>SUCCESS</info>');
        }
    }
}
