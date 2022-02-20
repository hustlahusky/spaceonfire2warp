<?php

declare(strict_types=1);

namespace App\Parallel;

use Amp\Coroutine;
use Amp\Parallel\Worker;
use Amp\Promise;
use Amp\Success;
use function Amp\call;

final class SelfProcessWorker implements Worker\Pool, Worker\WorkerFactory
{
    private bool $idle = true;

    public function __construct(
        private readonly Worker\Environment $environment = new Worker\BasicEnvironment(),
    ) {
    }

    public function create(): Worker\Worker
    {
        return $this;
    }

    public function getWorker(): Worker\Worker
    {
        return $this;
    }

    public function getWorkerCount(): int
    {
        return 1;
    }

    public function getIdleWorkerCount(): int
    {
        return $this->isIdle() ? 1 : 0;
    }

    public function getMaxSize(): int
    {
        return 1;
    }

    public function isRunning(): bool
    {
        return true;
    }

    public function isIdle(): bool
    {
        return $this->idle;
    }

    public function enqueue(Worker\Task $task): Promise
    {
        return new Coroutine(
            (function () use ($task) {
                $this->idle = false;

                try {
                    return yield call($task->run(...), $this->environment);
                } finally {
                    $this->idle = true;
                }
            })()
        );
    }

    public function shutdown(): Promise
    {
        return new Success(0);
    }

    public function kill(): void
    {
    }
}
