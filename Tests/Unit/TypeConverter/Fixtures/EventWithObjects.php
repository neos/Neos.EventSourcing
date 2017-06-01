<?php
namespace Neos\EventSourcing\Tests\Unit\TypeConverter\Fixtures;

use Neos\EventSourcing\Event\EventInterface;

final class EventWithObjects implements EventInterface
{
    /**
     * @var object
     */
    private $someObject;

    /**
     * @param object $someObject
     */
    public function __construct($someObject)
    {
        $this->someObject = $someObject;
    }

    public function getSomeObject()
    {
        return $this->someObject;
    }

}