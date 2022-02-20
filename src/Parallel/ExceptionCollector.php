<?php

declare(strict_types=1);

namespace App\Parallel;

use Amp\Parallel\Worker\TaskFailureThrowable;
use function Amp\Parallel\Sync\flattenThrowableBacktrace;
use function Amp\Parallel\Sync\formatFlattenedBacktrace;

/**
 * @internal
 */
final class ExceptionCollector implements \Countable
{
    /**
     * @var \Throwable[]
     */
    private array $exceptions = [];

    public function __construct()
    {
    }

    public function push(\Throwable $exception): void
    {
        $this->exceptions[] = $exception;
    }

    public function count(): int
    {
        return \count($this->exceptions);
    }

    public function getException(): \Throwable|null
    {
        $exceptions = $this->exceptions;
        $this->exceptions = [];

        if ([] === $exceptions) {
            return null;
        }

        if (1 === \count($exceptions)) {
            return $exceptions[0];
        }

        $output = null;
        foreach ($exceptions as $exception) {
            $output = self::convert($exception, $output);
        }

        return $output;
    }

    private static function convert(\Throwable $exception, \Throwable|null $previous = null): \Throwable
    {
        $originalTrace = $exception instanceof TaskFailureThrowable
            ? $exception->getOriginalTrace()
            : flattenThrowableBacktrace($exception);
        $originalTraceAsString = $exception instanceof TaskFailureThrowable
            ? $exception->getOriginalTraceAsString()
            : formatFlattenedBacktrace($originalTrace);

        return new class(
            $exception instanceof TaskFailureThrowable ? $exception->getOriginalClassName() : $exception::class,
            $originalTrace,
            $originalTraceAsString,
            $exception instanceof TaskFailureThrowable ? $exception->getOriginalMessage() : $exception->getMessage(),
            $exception instanceof TaskFailureThrowable ? $exception->getOriginalCode() : $exception->getCode(),
            $exception->getFile(),
            $exception->getLine(),
            $previous,
        ) extends \RuntimeException implements TaskFailureThrowable {
            /**
             * @param mixed[] $originalTrace
             */
            public function __construct(
                private readonly string $originalClassName,
                private readonly array $originalTrace,
                private readonly string $originalTraceAsString,
                string $message = '',
                int $code = 0,
                string $file = __FILE__,
                int $line = __LINE__,
                \Throwable|null $previous = null,
            ) {
                parent::__construct($message, $code, $previous);

                $this->file = $file;
                $this->line = $line;
            }

            public function getOriginalClassName(): string
            {
                return $this->originalClassName;
            }

            public function getOriginalMessage(): string
            {
                return $this->getMessage();
            }

            public function getOriginalCode()
            {
                return $this->getCode();
            }

            /**
             * @return mixed[]
             */
            public function getOriginalTrace(): array
            {
                return $this->originalTrace;
            }

            public function getOriginalTraceAsString(): string
            {
                return $this->originalTraceAsString;
            }
        };
    }
}
