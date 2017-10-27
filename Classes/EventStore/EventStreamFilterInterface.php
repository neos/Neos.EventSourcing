<?php
namespace Neos\EventSourcing\EventStore;

/*
 * This file is part of the Neos.EventSourcing package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

interface EventStreamFilterInterface
{
    /**
     * string representing the exact stream name
     */
    const FILTER_STREAM_NAME = 'streamName';

    /**
     * string representing a stream name prefix. Any stream starting with the same string matches this filter
     */
    const FILTER_STREAM_NAME_PREFIX = 'streamNamePrefix';

    /**
     * array of strings in the format ['Bounded.Context:SomeEvent', 'Bounded.Context:SomeOtherEvent', ...]
     */
    const FILTER_EVENT_TYPES = 'eventTypes';

    /**
     * integer with the minimum sequence number to be matched
     */
    const FILTER_MINIMUM_SEQUENCE_NUMBER = 'minimumSequenceNumber';

    /**
     * string representing the correlationId Metadata that has to match
     */
    const FILTER_CORRELATION_ID = 'correlationId';

    /**
     * This method is expected to return an array where the keys can be one or more of the FILTER_* constants
     *
     * @return array
     */
    public function getFilterValues(): array;

    /**
     * Return a specific filter value
     *
     * @param string $name Name of the filter value, must be one of the FILTER_* constants
     * @return mixed The specified filter value
     */
    public function getFilterValue(string $name);
}
