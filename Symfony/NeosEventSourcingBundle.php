<?php


namespace Neos\EventSourcing\Symfony;


use Neos\EventSourcing\Symfony\DependencyInjection\NeosEventSourcingExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NeosEventSourcingBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new NeosEventSourcingExtension();
    }

}