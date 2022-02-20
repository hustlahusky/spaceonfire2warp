<?php

declare(strict_types=1);

namespace App;

use Amp\File\Filesystem;
use Amp\Iterator;
use Amp\Producer;
use Amp\Promise;

/**
 * @implements Iterator<string>
 */
final class Finder implements Iterator
{
    /**
     * @param Iterator<string> $iterator
     */
    private function __construct(
        private readonly Iterator $iterator,
    ) {
    }

    public function advance(): Promise
    {
        return $this->iterator->advance();
    }

    public function getCurrent()
    {
        return $this->iterator->getCurrent();
    }

    public static function new(Filesystem $filesystem, string $directory, string|null $pattern = null): self
    {
        return new self(new Producer(static function (callable $emit) use ($filesystem, $directory, $pattern) {
            $list = Iterator\fromIterable(yield $filesystem->listFiles($directory));

            while (yield $list->advance()) {
                $file = $directory . \DIRECTORY_SEPARATOR . $list->getCurrent();

                if (yield $filesystem->isDirectory($file)) {
                    $iterator = self::new($filesystem, $file, $pattern);
                    while (yield $iterator->advance()) {
                        yield $emit($iterator->getCurrent());
                    }
                    continue;
                }

                if (null === $pattern || 0 < \preg_match($pattern, $file)) {
                    yield $emit($file);
                }
            }
        }));
    }
}
