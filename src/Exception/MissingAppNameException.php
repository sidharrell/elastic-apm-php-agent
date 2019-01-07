<?php

namespace PhilKra\Exception;

use Throwable;

/**
 * Application Tear Up has missing App Name in Config
 */
class MissingAppNameException extends \Exception
{
    public function __construct(string $message = null, int $code = null, Throwable $previous = null)
    {
        parent::__construct(sprintf('No app name registered in agent config. %s', $message), $code, $previous);
    }
}
