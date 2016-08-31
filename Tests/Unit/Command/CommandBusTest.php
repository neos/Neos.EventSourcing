<?php
namespace Neos\Cqrs\Tests\Unit\Command;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Neos\Cqrs\Command\CommandBus;
use Neos\Cqrs\Command\CommandHandlerInterface;
use Neos\Cqrs\Command\CommandInterface;
use Neos\Cqrs\Command\LocatorInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * CommandBusTest
 */
class CommandBusTest extends UnitTestCase
{
    const TEST_COMMAND_CLASSNAME = 'TestCommand';
    const TEST_COMMANDHANDLER_CLASSNAME = 'TestCommandHandler';

    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var LocatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockResolver;

    public function setUp()
    {
        $this->commandBus = new CommandBus();

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->inject($this->commandBus, 'objectManager', $this->mockObjectManager);

        $this->mockResolver = $this->createMock(LocatorInterface::class);
        $this->inject($this->commandBus, 'resolver', $this->mockResolver);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function createMockCommand()
    {
        return $this->getMockBuilder(CommandInterface::class)
            ->setMockClassName(self::TEST_COMMAND_CLASSNAME)
            ->getMock();
    }

    /**
     * @test
     */
    public function handleCommandWithExistingHandler()
    {
        $mockCommand = $this->createMockCommand();

        $mockCommandHandler = $this->createMock(CommandHandlerInterface::class);

        $mockCommandHandler
            ->expects($this->once())
            ->method('handle')
            ->with($mockCommand);

        $this->mockObjectManager
            ->expects($this->once())
            ->method('isRegistered')
            ->with(self::TEST_COMMANDHANDLER_CLASSNAME)
            ->willReturn(true);

        $this->mockObjectManager
            ->expects($this->once())
            ->method('get')
            ->with(self::TEST_COMMANDHANDLER_CLASSNAME)
            ->willReturn($mockCommandHandler);

        $this->mockResolver
            ->expects($this->once())
            ->method('resolve')
            ->with(self::TEST_COMMAND_CLASSNAME)
            ->willReturn(self::TEST_COMMANDHANDLER_CLASSNAME);

        $this->commandBus->handle($mockCommand);
    }
}
