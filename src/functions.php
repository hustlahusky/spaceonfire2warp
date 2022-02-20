<?php

declare(strict_types=1);

namespace App\Filesystem {
    const DS = \DIRECTORY_SEPARATOR;

    function normalizePath(string $path): string
    {
        return \rtrim(\str_replace(['/', '\\'], DS, $path), DS);
    }

    function workdir(string|null $workdir = null): string
    {
        static $cwd = null;

        if (null === $cwd || null !== $workdir) {
            $workdir ??= \getcwd() ?: null;

            if (null === $workdir) {
                throw new \RuntimeException('Unable to get current working directory.');
            }

            $cwd = normalizePath($workdir);
        }

        return $cwd;
    }

    function joinPath(string $path, string ...$rest): string
    {
        $path = normalizePath($path);
        $parts = [];

        while (true) {
            if (null === $path) {
                break;
            }

            [$left, $right] = \explode(DS, $path, 2) + [null, null];
            \assert(\is_string($left));

            if (null === $right) {
                $right = \array_shift($rest);
                if (null !== $right) {
                    $right = normalizePath($right);
                }
            }

            $path = $right;

            if ([] === $parts) {
                $parts[] = $left;
                continue;
            }

            if ('' === $left || '.' === $left) {
                continue;
            }

            if ('..' === $left) {
                \end($parts);
                if ('..' !== \current($parts)) {
                    \array_pop($parts);
                    continue;
                }
            }

            $parts[] = $left;
        }

        return \implode(DS, $parts);
    }

    function absolutePath(string $path): string
    {
        $path = normalizePath($path);
        return \str_starts_with($path, DS) ? $path : joinPath(workdir(), $path);
    }
}
