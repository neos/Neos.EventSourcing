<?php
namespace Ttree\Cqrs\Annotations;

/*
 * This file is part of the Medialib.Storage package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

/**
 * Used to enable property injection.
 *
 * Flow will build Dependency Injection code for the property and try
 * to inject a value as specified by the var annotation.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class EventHandler
{
    /**
     * @var string
     */
    public $event;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['event'])) {
            $this->event = (string)$values['event'];
        }
    }
}
