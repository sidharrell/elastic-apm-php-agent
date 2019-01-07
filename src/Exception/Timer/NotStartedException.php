<?php
namespace PhilKra\Exception\Timer;

use Throwable;

/**
 * Trying to stop a Timer that has not been started
 */
class NotStartedException extends \Exception {

  public function __construct( string $message = null, int $code = 0, Throwable $previous = NULL ) {
    parent::__construct( sprintf('Can\'t stop a timer which isn\'t started. %s', $message), $code, $previous );
  }

}
