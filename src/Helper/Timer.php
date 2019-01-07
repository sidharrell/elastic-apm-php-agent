<?php

namespace PhilKra\Helper;

use PhilKra\Exception\Timer\NotStartedException;
use PhilKra\Exception\Timer\NotStoppedException;

/**
 * Timer for Duration tracing
 */
class Timer
{
    /**
     * Starting Timestamp
     *
     * @var double
     */
    private $startedOn = null;

    /**
     * Ending Timestamp
     *
     * @var double
     */
    private $stoppedOn = null;

    /**
     * Start the Timer
     *
     * @return void
     */
    public function start()
    {
        $this->startedOn = microtime(true);
    }

    /**
     * Stop the Timer
     *
     * @throws \PhilKra\Exception\Timer\NotStartedException
     *
     * @return void
     */
    public function stop()
    {
        if ($this->startedOn === null) {
            throw new NotStartedException();
        }

        $this->stoppedOn = microtime(true);
    }

    /**
     * Get the elapsed Duration of this Timer
     *
     * @throws \PhilKra\Exception\Timer\NotStoppedException
     *
     * @return float
     */
    public function getDuration() : float
    {
        if ($this->stoppedOn === null) {
            throw new NotStoppedException();
        }

        return $this->toMicro($this->stoppedOn - $this->startedOn);
    }

    /**
     * Get the current elapsed Interval of the Timer
     *
     * @throws \PhilKra\Exception\Timer\NotStartedException
     *
     * @return float
     */
    public function getElapsed() : float
    {
        if ($this->startedOn === null) {
            throw new NotStartedException();
        }

        $return_value = 0;
        try {
            $return_value = ($this->stoppedOn === null) ?
                $this->toMicro(microtime(true) - $this->startedOn) :
                $this->getDuration();
        } catch (NotStoppedException $e) {
        }
        return $return_value;
    }

    /**
     * Convert the Duration from Seconds to Micro-Seconds
     *
     * @param  float $num
     *
     * @return float
     */
    private function toMicro(float $num) : float
    {
        return $num * 1000000;
    }
}
