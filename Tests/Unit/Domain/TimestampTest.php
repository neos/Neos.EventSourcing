<?php
namespace Neos\Cqrs\Tests\Unit\Domain;

/*
 * This file is part of the Neos.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Neos\Cqrs\Domain\Timestamp;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * TimestampTest
 */
class TimestampTest extends UnitTestCase
{
    /**
     * @test
     */
    public function createReturnDateTime()
    {
        $timestamp = Timestamp::create();
        $this->assertInstanceOf(\DateTime::class, $timestamp);
    }
}
