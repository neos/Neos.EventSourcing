<?php
namespace Neos\Cqrs\Tests\Unit\Command;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Neos\Cqrs\Command\CommandBus;
use Neos\Cqrs\Command\CommandInterface;
use Neos\Cqrs\Command\LocatorInterface;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * CommandBusTest
 */
class CommandBusTest extends UnitTestCase
{
    const TEST_COMMAND_SHORTNAME = 'TestCommand';
    const TEST_COMMANDHANDLER_SHORTNAME = 'TestCommandHandler';

    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var LocatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockResolver;

    /**
     * @var CommandInterface
     */
    protected $mockCommand;

    public function setUp()
    {
        $this->mockCommand = $this->getMockBuilder(CommandInterface::class)
            ->setMockClassName(self::TEST_COMMAND_SHORTNAME)
            ->getMock();

        $this->commandBus = new CommandBus();

        $this->mockResolver = $this->createMock(LocatorInterface::class);
        $this->inject($this->commandBus, 'resolver', $this->mockResolver);
    }

    /**
     * @test
     */
    public function handleCommandWithExistingHandler()
    {
        $this->mockResolver
            ->expects($this->once())
            ->method('resolve')
            ->with(self::TEST_COMMAND_SHORTNAME)
            ->willReturn(function () {
            });

        $this->commandBus->handle($this->mockCommand);
    }
}
