<?php

namespace PhilKra\Stores;

use PhilKra\Events\Span;
use PhilKra\Exception\Span\DuplicateSpanNameException;

/**
 *
 * Store for the Transaction Events
 *
 */
class SpansStore extends Store
{
    /**
     * Register a Span
     *
     * @throws \PhilKra\Exception\Span\DuplicateSpanNameException
     *
     * @param \PhilKra\Events\Span $span
     *
     * @return void
     */
    public function register(Span $span)
    {
        $name = $span->getSpanName();

        // Do not override the
        if (isset($this->store[$name]) === true) {
            throw new DuplicateSpanNameException($name);
        }

        // Push to Store
        $this->store[$name] = $span;
    }

    /**
     * Fetch a Span from the Store
     *
     * @param string $name
     *
     * @return mixed: \PhilKra\Events\Span | null
     */
    public function fetch(string $name)
    {
        return $this->store[$name] ?? null;
    }

    /**
     * Serialize the Spans Events Store
     *
     * @return array
     */
    public function jsonSerialize() : array
    {
        return array_values($this->store);
    }
}
