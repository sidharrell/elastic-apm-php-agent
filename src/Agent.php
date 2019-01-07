<?php

namespace PhilKra;

use PhilKra\Events\DefaultEventFactory;
use PhilKra\Events\EventFactoryInterface;
use PhilKra\Stores\ErrorsStore;
use PhilKra\Stores\TransactionsStore;
use PhilKra\Events\Transaction;
use PhilKra\Helper\Timer;
use PhilKra\Helper\Config;
use PhilKra\Middleware\Connector;
use PhilKra\Exception\Transaction\UnknownTransactionException;

/**
 *
 * APM Agent
 *
 * @link https://www.elastic.co/guide/en/apm/server/master/transaction-api.html
 *
 */
class Agent
{
    /**
     * Agent Version
     *
     * @var string
     */
    const VERSION = '6.4.2';

    /**
     * Agent Name
     *
     * @var string
     */
    const NAME = 'elastic-php';

    /**
     * Config Store
     *
     * @var \PhilKra\Helper\Config
     */
    private $config;

    /**
     * Transactions Store
     *
     * @var \PhilKra\Stores\TransactionsStore
     */
    private $transactionsStore;

    /**
     * Error Events Store
     *
     * @var \PhilKra\Stores\ErrorsStore
     */
    private $errorsStore;

    /**
     * Apm Timer
     *
     * @var \PhilKra\Helper\Timer
     */
    private $timer;

    /**
     * Common/Shared Contexts for Errors and Transactions
     *
     * @var array
     */
    private $sharedContext = [
      'user'   => [],
      'custom' => [],
      'tags'   => []
    ];

    /**
     * @var EventFactoryInterface
     */
    private $eventFactory;

    /**
     * Setup the APM Agent
     *
     * @param array                 $config
     * @param array                 $sharedContext Set shared contexts such as user and tags
     * @param EventFactoryInterface $eventFactory  Alternative factory to use when creating event objects
     *
     * @return void
     */
    public function __construct(array $config, array $sharedContext = [], EventFactoryInterface $eventFactory = null)
    {
        // Init Agent Config
        try {
            $this->config = new Config($config);
        } catch (Exception\MissingAppNameException $e) {
        }

        // Use the custom event factory or create a default one
        $this->eventFactory = $eventFactory ?? new DefaultEventFactory();

        // Init the Shared Context
        $this->sharedContext['user']   = $sharedContext['user'] ?? [];
        $this->sharedContext['custom'] = $sharedContext['custom'] ?? [];
        $this->sharedContext['tags']   = $sharedContext['tags'] ?? [];

        // Let's misuse the context to pass the Environment Var config
        // to the EventBeans and the getContext method
        // @see https://github.com/philkra/elastic-apm-php-agent/issues/27
        $this->sharedContext['env'] = ( $this->config->get( 'env' ) === null )
            ? []
            : $this->config->get( 'env' );

        // Initialize Event Stores
        $this->transactionsStore = new TransactionsStore();
        $this->errorsStore       = new ErrorsStore();

        // Start Global Agent Timer
        $this->timer = new Timer();
        $this->timer->start();
    }

    /**
     * Start the Transaction capturing
     *
     * @throws \PhilKra\Exception\Transaction\DuplicateTransactionNameException
     *
     * @param string $name
     * @param array  $context
     *
     * @return Transaction
     */
    public function startTransaction(string $name, array $context = []): Transaction
    {
        // Create and Store Transaction
        $this->transactionsStore->register(
            $this->eventFactory->createTransaction($name, array_replace_recursive($this->sharedContext, $context))
        );

        // Start the Transaction
        $transaction = $this->transactionsStore->fetch($name);
        $transaction->start();

        return $transaction;
    }

    /**
     * Stop the Transaction
     *
     * @throws \PhilKra\Exception\Transaction\UnknownTransactionException
     *
     * @param string $name
     * @param array $meta, Def: []
     *
     * @return void
     */
    public function stopTransaction(string $name, array $meta = [])
    {
        $this->getTransaction($name)->stop();
        $this->getTransaction($name)->setMeta($meta);
    }

    /**
     * Get a Transaction
     *
     * @throws \PhilKra\Exception\Transaction\UnknownTransactionException
     *
     * @param string $name
     *
     * @return Transaction
     */
    public function getTransaction(string $name)
    {
        $transaction = $this->transactionsStore->fetch($name);
        if ($transaction === null) {
            throw new UnknownTransactionException($name);
        }

        return $transaction;
    }

    /**
     * Register a Thrown Exception, Error, etc.
     *
     * @link http://php.net/manual/en/class.throwable.php
     *
     * @param \Throwable $thrown
     * @param array      $context
     *
     * @return void
     */
    public function captureThrowable(\Throwable $thrown, array $context = [])
    {
        $this->errorsStore->register(
            $this->eventFactory->createError($thrown, array_replace_recursive($this->sharedContext, $context))
        );
    }

    /**
     * Get the Agent Config
     *
     * @return \PhilKra\Helper\Config
     */
    public function getConfig() : Config
    {
        return $this->config;
    }

    /**
     * Send Data to APM Service
     *
     * @link https://github.com/philkra/elastic-apm-laravel/issues/22
     *
     * @return bool
     */
    public function send() : bool
    {
        // Is the Agent enabled ?
        if ($this->config->get('active') === false) {
            return false;
        }

        $connector = new Connector($this->config);
        $status = true;

        // Commit the Errors
        if ($this->errorsStore->isEmpty() === false) {
            $status = $status && $connector->sendErrors($this->errorsStore);
            if ($status === true) {
                $this->errorsStore->reset();
            }
        }

        // Commit the Transactions
        if ($this->transactionsStore->isEmpty() === false) {
            $status = $status && $connector->sendTransactions($this->transactionsStore);
            if ($status === true) {
                $this->transactionsStore->reset();
            }
        }

        return $status;
    }
}
