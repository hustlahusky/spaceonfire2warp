<?php

declare(strict_types=1);

namespace App\Parallel;

use Amp\Deferred;
use Amp\Iterator;
use Amp\Parallel\Worker\Pool;
use Amp\Parallel\Worker\Task;
use Amp\Promise;
use Amp\Success;
use function Amp\call;

final class TaskScheduler
{
    /**
     * @var Deferred<void>
     */
    private Deferred $deferred;

    /**
     * @var \SplObjectStorage<Promise<mixed>,null>
     */
    private \SplObjectStorage $storage;

    /**
     * @var Promise<bool>|null
     */
    private Promise|null $advance = null;

    /**
     * @var null|callable(Task):void
     */
    private $taskPush;

    /**
     * @var null|callable(Promise<mixed>):callable
     */
    private $taskOnResolve;

    private ExceptionCollector $exceptionCollector;

    /**
     * @param Iterator<Task> $taskProducer
     */
    public function __construct(
        private readonly Pool $pool,
        private readonly Iterator $taskProducer,
    ) {
        $this->deferred = new Deferred();
        $this->storage = new \SplObjectStorage();
        $this->exceptionCollector = new ExceptionCollector();
    }

    /**
     * @return Promise<void>
     */
    public function run(): Promise
    {
        return call(function () {
            for ($i = $this->pool->getMaxSize(); 0 < $i; $i--) {
                $task = yield $this->pullTask();
                if (null === $task) {
                    break;
                }

                self::getPushFunc($this)($task);
            }

            return $this->deferred->promise();
        });
    }

    /**
     * @return Promise<bool>
     */
    private function advance(): Promise
    {
        // Stop scheduling new tasks after exception occurs
        if (0 < $this->exceptionCollector->count()) {
            return new Success(false);
        }

        $deferred = new Deferred();

        $taskProducer = $this->taskProducer;
        ($this->advance ?? new Success(true))->onResolve(
            static function (\Throwable|null $e, bool $v) use ($deferred, $taskProducer): void {
                if (null !== $e) {
                    $deferred->fail($e);
                    return;
                }

                if (!$v) {
                    $deferred->resolve(false);
                    return;
                }

                $advance = $taskProducer->advance();
                $advance->onResolve(static function (\Throwable|null $e, bool $v) use ($deferred): void {
                    if (null !== $e) {
                        $deferred->fail($e);
                        return;
                    }

                    $deferred->resolve($v);
                });
            }
        );

        $this->advance = $promise = $deferred->promise();
        $promise->onResolve(function () use ($promise): void {
            if ($this->advance === $promise) {
                $this->advance = null;
            }
        });

        return $promise;
    }

    /**
     * @return Promise<Task|null>
     */
    private function pullTask(): Promise
    {
        return call(function () {
            if (yield $this->advance()) {
                return $this->taskProducer->getCurrent();
            }

            return null;
        });
    }

    private function resolve(\Throwable|null $exception = null): void
    {
        if ($this->deferred->isResolved()) {
            return;
        }

        if (null !== $exception) {
            $this->exceptionCollector->push($exception);
        }

        if (0 < $this->storage->count()) {
            return;
        }

        $this->storage = new \SplObjectStorage();
        $this->taskPush = null;
        $this->taskOnResolve = null;
        $this->advance = null;

        $exception = $this->exceptionCollector->getException();
        if (null === $exception) {
            $this->deferred->resolve();
        } else {
            $this->deferred->fail($exception);
        }
    }

    private static function getPushFunc(self $scheduler): callable
    {
        if (\is_callable($scheduler->taskPush)) {
            return $scheduler->taskPush;
        }

        $pool = $scheduler->pool;
        $storage = $scheduler->storage;
        $onResolve = self::getOnTaskResolveFunc($scheduler);

        return $scheduler->taskPush = static function (Task $task) use ($pool, $storage, &$onResolve): void {
            $promise = $pool->enqueue($task);
            $storage->attach($promise);
            $promise->onResolve($onResolve($promise));
        };
    }

    private static function getOnTaskResolveFunc(self $scheduler): callable
    {
        if (\is_callable($scheduler->taskOnResolve)) {
            return $scheduler->taskOnResolve;
        }

        return $scheduler->taskOnResolve = static function (Promise $promise) use ($scheduler): callable {
            return static function ($e) use ($scheduler, $promise) {
                $scheduler->storage->detach($promise);

                if ($e) {
                    $scheduler->resolve($e);
                    return;
                }

                $task = yield $scheduler->pullTask();

                if (null !== $task) {
                    (self::getPushFunc($scheduler))($task);
                    return;
                }

                $scheduler->resolve();
            };
        };
    }
}
