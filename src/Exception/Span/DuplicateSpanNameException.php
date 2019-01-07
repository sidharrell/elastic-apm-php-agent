<?php

namespace PhilKra\Exception\Span;

use Throwable;

/**
 * Trying to register a already registered Transaction
 */
class DuplicateSpanNameException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('A span with the name %s is already registered.', $message), $code, $previous);
    }
}
