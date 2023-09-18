<?php

namespace Armie;

use Armie\Errors\SystemError;
use Armie\Interfaces\Promise\PromiseFinal;
use Armie\Interfaces\Promise\PromiseThen;
use Armie\Tasks\Task;
use Closure;
use Fiber;
use FiberError;
use Throwable;

use function Armie\Helpers\report;

/**
 * Promises for async operations - built on @see Fibers.
 *
 * Armie Framework
 *
 * @copyright busarm.com
 * @license https://github.com/busarm/armie/blob/master/LICENSE (MIT License)
 *
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
 * @template T
 * 
 * // TODO Investigate using fiber child process if fiber worker not available
 */
class Promise implements PromiseThen
{
    private Fiber $_fiber;

    private bool $_done = false;
    private mixed $_result = null;

    private ?Closure $_catchFn = null;
    private ?Closure $_finallyFn = null;

    /**
     * @param Fiber|Task<T>|callable():T $task
     *
     * @throws FiberError
     */
    public function __construct(Fiber|Task|callable $task)
    {
        if ($task instanceof Fiber) {
            if (!$task->isStarted()) {
                $task->start();
            }
            if ($task->isTerminated()) {
                throw new FiberError();
            }
            $this->_fiber = $task;
        } else {
            $this->_fiber = Async::withFiberWorker($task);
        }
    }

    /**
     * Promise has completed.
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
        $thenfn = function () use ($fn) {
            try {
                $this->wait();
                $this->_result = call_user_func($fn, $this->_result) ?? $this->_result;
            } catch (Throwable $th) {
                $this->_catchFn ? call_user_func($this->_catchFn, $th) : report()->exception($th);
            } finally {
                $this->_finallyFn && call_user_func($this->_finallyFn);
            }

            return $this->_result;
        };

        try {
            Async::withEventLoop($thenfn);
        } catch (SystemError) {
            Async::withChildProcess($thenfn) ?: $thenfn();
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
     * Wait for process to complete (with response).
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
     * Resolve list of promises.
     *
     * @param self[] $promises List of Promises
     *
     * @return self<array<T|bool>>
     */
    public static function all(array $promises): self
    {
        $fiber = new Fiber(
            function (array $promises) {
                Fiber::suspend();

                return array_map(
                    fn (self $promise) => $promise->wait(),
                    $promises
                );
            }
        );
        $fiber->start($promises);

        return new self($fiber);
    }

    /**
     * Resolve promise.
     *
     * @param self<T> $promise Promise
     *
     * @return T
     */
    public static function resolve(self $promise): mixed
    {
        return $promise->wait();
    }
}
