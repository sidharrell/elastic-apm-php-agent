<?php

namespace PhilKra\Events;

use PhilKra\Exception\Span\DuplicateSpanNameException;
use PhilKra\Exception\Timer\NotStartedException;
use PhilKra\Exception\Timer\NotStoppedException;
use PhilKra\Helper\Timer;
use PhilKra\Exception\Span\UnknownSpanException;

/**
 *
 * Abstract Transaction class for all inheriting Transactions
 *
 * @link https://www.elastic.co/guide/en/apm/server/master/transaction-api.html
 *
 */
class Transaction extends EventBean implements \JsonSerializable
{
    /**
     * Transaction Name
     *
     * @var string
     */
    private $name;

    /**
     * Transaction Timer
     *
     * @var \PhilKra\Helper\Timer
     */
    private $timer;

    /**
     * Summary of this Transaction
     *
     * @var array
     */
    private $summary = [
        'duration'  => 0.0,
        'backtrace' => null,
        'headers'   => []
    ];

    /**
     * @var EventFactoryInterface
     */
    private $eventFactory;

    /**
     * Spans Store
     *
     * @var \PhilKra\Stores\SpansStore
     */
    private $spansStore;

    /**
     * Common/Shared Contexts for Spans
     *
     * @var array
     */
    private $sharedContext = [];

    /**
     * The spans for the transaction
     *
     * @var array
     */
    private $spans = [];

    /**
    * Create the Transaction
    *
    * @param string $name
    * @param array $contexts
    */
    public function __construct(string $name, array $contexts)
    {
        parent::__construct($contexts);
        $this->setTransactionName($name);
        $this->timer = new Timer();
    }

    /**
    * Start the Transaction
    *
    * @return void
    */
    public function start()
    {
        $this->timer->start();
    }

    /**
     * Stop the Transaction
     *
     * @param integer|null $duration
     *
     * @return void
     */
    public function stop(int $duration = null)
    {
        // Stop the Timer
        try {
            $this->timer->stop();
        } catch (NotStartedException $e) {
        }

        // Store Summary
        try {
            $this->summary['duration'] = $duration ?? round($this->timer->getDuration(), 3);
        } catch (NotStoppedException $e) {
        }
        $this->summary['headers']   = (function_exists('xdebug_get_headers') === true) ? xdebug_get_headers() : [];
        $this->summary['backtrace'] = debug_backtrace();
    }

    /**
    * Set the Transaction Name
    *
    * @param string $name
    *
    * @return void
    */
    public function setTransactionName(string $name)
    {
        $this->name = $name;
    }

    /**
    * Get the Transaction Name
    *
    * @return string
    */
    public function getTransactionName() : string
    {
        return $this->name;
    }

    /**
    * Get the Summary of this Transaction
    *
    * @return array
    */
    public function getSummary() : array
    {
        return $this->summary;
    }

    /**
     * Set the spans for the transaction
     *
     * @param array $spans
     *
     * @return void
     */
    public function setSpans(array $spans)
    {
        $this->spans = $spans;
    }

    /**
     * Get the spans from the transaction
     *
     * @return array
     */
    private function getSpans(): array
    {
        return $this->spans;
    }


    /**
     * start a span for the transaction
     *
     * @param string $name
     * @param array $context
     * @return Span
     */
    public function startSpan(string $name, array $context = []): Span
    {
        // Create and Store Span
        try {
            $this->spansStore->register(
                $this->eventFactory->createSpan($name, array_replace_recursive($this->sharedContext, $context))
            );
        } catch (DuplicateSpanNameException $e) {
        }

        // Start the Transaction
        $span = $this->spansStore->fetch($name);
        $span->start();

        return $span;
    }

    /**
     * Stop the Span
     *
     * @throws \PhilKra\Exception\Span\UnknownSpanException
     *
     * @param string $name
     * @param array $meta, Def: []
     *
     * @return void
     */
    public function stopSpan(string $name, array $meta = [])
    {
        $this->getSpan($name)->stop();
        $this->getSpan($name)->setMeta($meta);
    }

    /**
     * Get a Transaction
     *
     * @throws \PhilKra\Exception\Span\UnknownSpanException
     *
     * @param string $name
     *
     * @return Span
     */
    public function getSpan(string $name)
    {
        $span = $this->spansStore->fetch($name);
        if ($span === null) {
            throw new UnknownSpanException($name);
        }

        return $span;
    }


    /**
    * Serialize Transaction Event
    *
    * @return array
    */
    public function jsonSerialize() : array
    {
        return [
          'id'        => $this->getId(),
          'timestamp' => $this->getTimestamp(),
          'name'      => $this->getTransactionName(),
          'duration'  => $this->summary['duration'],
          'type'      => $this->getMetaType(),
          'result'    => $this->getMetaResult(),
          'context'   => $this->getContext(),
          'spans'     => $this->getSpans(),
          'processor' => [
              'event' => 'transaction',
              'name'  => 'transaction',
          ]
      ];
    }
}
