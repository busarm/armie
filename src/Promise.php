<?php

namespace Armie;

use Armie\Interfaces\Promise\PromiseCatch;
use Armie\Interfaces\Promise\PromiseFinal;
use Armie\Interfaces\Promise\PromiseThen;
use Armie\Tasks\CallableTask;
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
class Promise implements PromiseThen, PromiseCatch, PromiseFinal
{
    private string $_id;
    private Fiber $_fiber;
    private ?Closure $_thenFn = null;
    private ?Closure $_catchFn = null;
    private ?Closure $_finallyFn = null;

    /**
     * @param Task|callable(array $params):T $task 
     */
    public function __construct(Task|callable $task)
    {
        $task = $task instanceof Task ? $task : new CallableTask(Closure::fromCallable($task));

        $this->_id = $task->getName();
        $this->_fiber = Async::withFiberWorker($this->_id, strval($task->getRequest(false)), true);
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
}
