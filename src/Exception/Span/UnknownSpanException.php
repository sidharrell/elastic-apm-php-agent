<?php

namespace PhilKra\Exception\Span;

/**
 * Trying to fetch an unregistered Transaction
 */
class UnknownSpanException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('The span "%s" is not registered.', $message), $code, $previous);
    }
}
