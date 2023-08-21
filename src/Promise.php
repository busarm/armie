<?php

namespace Armie;

use Armie\Interfaces\Promise\PromiseFinal;
use Armie\Interfaces\Promise\PromiseThen;
use Armie\Tasks\Task;
use Closure;
use Fiber;
use Generator;
use Throwable;

use function Armie\Helpers\report;

/**
 * Promises for async operations - built on @see Fibers 
 * 
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 * @inheritDoc
 * 
 * ## CAUTION ! ##
 * Be careful when using parameters with a callable `task`. 
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
class Promise implements PromiseThen
{
    private Fiber $_fiber;

    private bool $_resolving = false;
    private bool $_done = false;
    private mixed $_result = null;

    private ?Closure $_catchFn = null;
    private ?Closure $_finallyFn = null;

    /**
     * @param Task|callable():T $task 
     */
    public function __construct(Task|callable $task)
    {
        $this->_fiber = Async::withFiberWorker($task, true);
    }

    /**
     * Promise has completed
     */
    public function done()
    {
        return $this->_done;
    }

    /**
     * @inheritDoc
     */
    public function then(callable $fn): PromiseThen
    {
        Async::withEventLoop(
            function () use ($fn) {
                try {
                    $this->wait();
                    $this->_result = call_user_func($fn, $this->_result) ?? $this->_result;
                } catch (Throwable $th) {
                    $this->_catchFn ? call_user_func($this->_catchFn, $th) : report()->exception($th);
                } finally {
                    $this->_finallyFn && call_user_func($this->_finallyFn);
                }
                return $this->_result;
            }
        );

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
    protected function wait(): mixed
    {
        while ($this->_fiber->isSuspended() && !$this->_fiber->isTerminated()) {
            $this->_fiber->resume();
        }

        $this->_result = $this->_fiber->getReturn();
        $this->_done = true;

        return $this->_result;
    }

    /**
     * Resolve list of promises
     * 
     * @param self[] $promises List of Promises
     * @return Generator
     */
    public static function all(array $promises): Generator
    {
        foreach ($promises as $key => $promise) {
            yield $key => $promise->wait();
        }
    }

    /**
     * Resolve promise
     * 
     * @param self $promise Promise
     * @return T
     */
    public static function resolve(self $promise): mixed
    {
        return $promise->wait();
    }
}
