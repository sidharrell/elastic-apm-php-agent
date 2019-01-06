<?php

namespace PhilKra\Events;

use PhilKra\Helper\Timer;

/**
 *
 * Abstract Span class for all inheriting Spans
 *
 * @link https://www.elastic.co/guide/en/apm/server/master/transaction-api.html
 *
 */
class Span extends EventBean implements \JsonSerializable
{
    /**
     * Span Name
     *
     * @var string
     */
    private $name;

    /**
     * Span Timer
     *
     * @var \PhilKra\Helper\Timer
     */
    private $timer;

    /**
     * Span Duration
     *
     * @var \PhilKra\Helper\Timer
     */
    private $duration;

    /**
     * Create the Span
     *
     * @param string $name
     * @param array $contexts
     */
    public function __construct(string $name, array $contexts)
    {
        parent::__construct($contexts);
        $this->setSpanName($name);
        $this->timer = new Timer();
    }


    /**
     * Set the Span Name
     *
     * @param string $name
     *
     * @return void
     */
    public function setSpanName(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get the Span Name
     *
     * @return string
     */
    public function getSpanName() : string
    {
        return $this->name;
    }

    /**
     * Get the Span Duration
     *
     * @return string
     */
    public function getSpanDuration() : string
    {
        return $this->duration;
    }


    /**
     * Start the Span
     *
     * @return void
     */
    public function start()
    {
        $this->timer->start();
    }

    /**
     * Stop the Span
     *
     * @param integer|null $duration
     *
     * @return void
     */
    public function stop(int $duration = null)
    {
        // Stop the Timer
        $this->timer->stop();

        // Store Summary
        $this->duration = $duration ?? round($this->timer->getDuration(), 3);
    }




    /**
     * Serialize Span Event
     *
     * @return array
     */
    public function jsonSerialize() : array
    {
        return [
            'id'        => $this->getId(),
            'timestamp' => $this->getTimestamp(),
            'name'      => $this->getSpanName(),
            'duration'  => $this->getSpanDuration(),
            'type'      => $this->getMetaType(),
            'result'    => $this->getMetaResult(),
            'context'   => $this->getContext()
        ];
    }
}
