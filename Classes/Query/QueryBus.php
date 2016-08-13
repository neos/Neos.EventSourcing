<?php
namespace Flowpack\Cqrs\Query;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Message\MessageBusInterface;
use Flowpack\Cqrs\Message\MessageInterface;
use Flowpack\Cqrs\Message\MessageResultInterface;
use Flowpack\Cqrs\Message\Resolver\ResolverInterface;
use Flowpack\Cqrs\Query\Exception\QueryBusException;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 * QueryHandlerException
 *
 * @Flow\Scope("singleton")
 */
class QueryBus implements MessageBusInterface
{
    /**
     * @var ObjectManagerInterface
     * @Flow\Inject
     */
    protected $objectManager;

    /**
     * @var ResolverInterface
     * @Flow\Inject
     */
    protected $resolver;

    /**
     * @param MessageInterface|QueryInterface $query
     * @return MessageResultInterface
     */
    public function handle(MessageInterface $query)
    {
        return $this->getHandler($query)
            ->handle($query);
    }

    /**
     * @param MessageInterface|QueryInterface $message
     * @return QueryHandlerInterface
     * @throws QueryBusException
     */
    protected function getHandler(QueryInterface $message)
    {
        $messageName = $message->getName();

        $handlerId = $this->resolver->resolve($messageName);

        if (!$this->objectManager->isRegistered($handlerId)) {
            throw new QueryBusException(
                sprintf(
                    "Cannot instantiate handler '%s' for query '%s'",
                    $handlerId,
                    $messageName
                )
            );
        }

        /** @var QueryHandlerInterface $handler */
        $handler = $this->objectManager->get($handlerId);

        if (!$handler instanceof QueryHandlerInterface) {
            throw new QueryBusException(
                sprintf(
                    "Handler '%s' returned by locator for query '%s' should implement QueryHandlerInterface",
                    $handlerId,
                    $messageName
                )
            );
        }

        return $handler;
    }
}
