<?php
namespace Ttree\Cqrs\Tests\Unit;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Command\CommandBus;
use Ttree\Cqrs\Command\CommandHandlerInterface;
use Ttree\Cqrs\Command\CommandInterface;
use Ttree\Cqrs\Message\Resolver\ResolverInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Eel context test
 */
class CommandBusTest extends UnitTestCase
{
    const TEST_COMMAND_CLASSNAME = 'TestCommand';
    const TEST_COMMANDHANDLER_CLASSNAME = 'TestCommandHandler';

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var ResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockResolver;

    /**
     * @return CommandBus
     */
    public function createCommandBus()
    {
        $commandBus = new CommandBus();

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        ObjectAccess::setProperty($commandBus, 'objectManager', $this->mockObjectManager, true);

        $this->mockResolver = $this->createMock(ResolverInterface::class);
        ObjectAccess::setProperty($commandBus, 'resolver', $this->mockResolver, true);

        return $commandBus;
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
     * @expectedException \Ttree\Cqrs\Command\Exception\CommandBusException
     */
    public function handleCommandWithoutHandlerThrowException()
    {
        $commandBus = $this->createCommandBus();

        $commandBus->handle($this->createMockCommand());
    }

    /**
     * @test
     * @expectedException \Ttree\Cqrs\Command\Exception\CommandBusException
     */
    public function handleCommandWithUnregistredHandlerThrowException()
    {
        $commandBus = $this->createCommandBus();
        $this->mockResolver
            ->expects($this->once())
            ->method('resolve')
            ->with(self::TEST_COMMAND_CLASSNAME)
            ->willReturn(self::TEST_COMMANDHANDLER_CLASSNAME);

        $mockCommand = $this->createMockCommand();

        $commandBus->handle($mockCommand);
    }

    /**
     * @test
     */
    public function handleCommandWithExistingHandler()
    {
        $commandBus = $this->createCommandBus();

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

        $commandBus->handle($mockCommand);
    }
}
