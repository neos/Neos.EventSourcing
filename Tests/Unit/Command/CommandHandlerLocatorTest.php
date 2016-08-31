<?php
namespace Neos\Cqrs\Tests\Unit\Command;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Neos\Cqrs\Command\CommandHandlerInterface;
use Neos\Cqrs\Command\CommandHandlerLocator;
use Neos\Cqrs\Command\CommandInterface;
use Neos\Cqrs\Command\LocatorInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * CommandHandlerLocatorTest
 */
class CommandHandlerLocatorTest extends UnitTestCase
{
    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var LocatorInterface
     */
    protected $commandHandlerLocator;

    public function setUp()
    {
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->commandHandlerLocator = new CommandHandlerLocator();
        $this->inject($this->commandHandlerLocator, 'objectManager', $this->mockObjectManager);
    }

    /**
     * @test
     * @expectedException \Neos\Cqrs\Command\Exception\CommandHandlerNotFoundException
     */
    public function resolveCommandWithoutHandlerThrowException()
    {
        $this->commandHandlerLocator->resolve('\Your\Package\ActionCommand');
    }

    /**
     * @test
     */
    public function resultCallCommandHandler()
    {
        $this->inject($this->commandHandlerLocator, 'map', [
            '\Your\Package\ActionCommand' => [
                '\Your\Package\CommandHandler',
                'handleActionCommand'
            ]
        ]);

        $mockCommand = $this->createMock(CommandInterface::class);
        $mockCommandHandler = $this->getMockBuilder(CommandHandlerInterface::class)
            ->setMethods(['handleActionCommand'])
            ->getMock();

        $mockCommandHandler->expects($this->once())
            ->method('handleActionCommand')
            ->with($mockCommand);

        $this->mockObjectManager->expects($this->once())
            ->method('get')
            ->with('\Your\Package\CommandHandler')
            ->willReturn($mockCommandHandler);

        $closure = $this->commandHandlerLocator->resolve('\Your\Package\ActionCommand');
        $closure($mockCommand);
    }
}
