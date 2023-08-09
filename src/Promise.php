<?php

namespace Busarm\PhpMini;

use Busarm\PhpMini\Interfaces\Promise\PromiseCatch;
use Busarm\PhpMini\Interfaces\Promise\PromiseFinal;
use Busarm\PhpMini\Interfaces\Promise\PromiseThen;
use Busarm\PhpMini\Tasks\CallableTask;
use Closure;
use Fiber;
use Generator;
use Throwable;

use function Busarm\PhpMini\Helpers\app;
use function Busarm\PhpMini\Helpers\report;

/**
 * Promises for async operations - built on @see Fibers 
 * 
 * PHP Mini Framework
 *
 * @copyright busarm.com
 * @license https://github.com/Busarm/php-mini/blob/master/LICENSE (MIT License)
 * @inheritDoc
 * 
 * ## CAUTION ! ##
 * Be careful when using parameters with the `callable`. 
 * Parameters will also be serialized before sending to the task worker.
 * Hence, ensure that the parameters are simple. 
 * 
 * E.g 
 * 
 * #### DON'T do this: ####
 * ```
 * new Promise(function () use ($app) { 
 *      $secret = $app->config->secret; 
 *      // Use secret
 * });
 * ```
 * 
 * #### DO this: ####
 * ```
 * $secret = $app->config->secret; 
 * new Promise(function () use ($secret) { 
 *      // Use secret
 * });
 * ```
 * 
 * #### or DO this: ####
 * ```
 * new Promise(function ($secret) { 
 *      // Use secret
 * }, $app->config->secret);
 * 
 * ```
 * @template T
 */
class Promise implements PromiseThen, PromiseCatch, PromiseFinal
{
    private string $_id;
    private Fiber $_fiber;
    private ?Closure $_thenFn = null;
    private ?Closure $_catchFn = null;
    private ?Closure $_finallyFn = null;

    /**
     * @param callable(array $params):T $callable 
     * @param array $params
     */
    public function __construct(callable $callable, ...$params)
    {
        $this->_id = static::class . "::" . uniqid();
        $task = new CallableTask(Closure::fromCallable($callable), $params);
        $body = strval($task->getRequest(false, app()->config->secret));
        $this->_fiber = Async::withFiberWorker($this->_id, $body, true);
    }

    /**
     * Get promise Id
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @inheritDoc
     */
    public function then(callable $fn): PromiseCatch
    {
        $promise = &$this;
        $this->_thenFn = Closure::fromCallable(function ($data) use ($fn, $promise) {
            try {
                return call_user_func($fn, $data);
            } catch (Throwable $th) {
                $promise->_catchFn ? call_user_func($promise->_catchFn, $th) : report()->exception($th);
            } finally {
                $promise->_finallyFn && call_user_func($promise->_finallyFn);
            }
            return null;
        });

        try {
            if ($this->_fiber->isSuspended() && !$this->_fiber->isTerminated()) {
                $this->_fiber->resume($this->_thenFn);
            } else {
                call_user_func($this->_thenFn, $this->_fiber->getReturn());
            }
        } catch (Throwable $th) {
            $this->_catchFn ? call_user_func($this->_catchFn, $th) : report()->exception($th);
        } finally {
            $this->_finallyFn && call_user_func($this->_finallyFn);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function catch(callable $fn): PromiseFinal
    {
        $this->_catchFn = Closure::fromCallable($fn);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function finally(callable $fn): void
    {
        $this->_finallyFn = Closure::fromCallable($fn);
    }

    /**
     * Wait for process to complete (with response)
     * 
     * @return T
     */
    public function wait(): mixed
    {
        $suspended = null;

        // Resume if suspended and nothong else is running
        while ($this->_fiber->isSuspended() && !$this->_fiber->isTerminated()) {
            if (
                !Fiber::getCurrent()
                || Fiber::getCurrent()->isSuspended()
                || Fiber::getCurrent()->isTerminated()
            ) {
                $suspended = $this->_fiber->resume($suspended);
            }
        }

        return $this->_fiber->getReturn();
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public static function suspend($value = null)
    {
        return Fiber::suspend($value);
    }

    /**
     * Run tasks concurrently
     * 
     * @param callable[] $tasks List of Task instance to run
     * @return Generator
     */
    public static function all(array $tasks): Generator
    {
        /** @var self[] */
        $promises = [];
        foreach ($tasks as $key => $task) {
            $promises[$key] = new self($task);
        }
        ksort($promises);
        foreach ($promises as $key => $promise) {
            yield $key => $promise->wait();
        }
    }
}
