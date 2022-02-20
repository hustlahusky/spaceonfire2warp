<?php

declare(strict_types=1);

namespace App;

use Amp\Promise;
use Yiisoft\Arrays\ArrayHelper;
use function Amp\call;
use function Amp\File\filesystem;
use function App\Filesystem\absolutePath;
use function App\Filesystem\joinPath;

final class Composer
{
    /**
     * @param string $filename
     * @param array<string,mixed> $data
     */
    private function __construct(
        private readonly string $filename,
        private readonly array $data,
    ) {
    }

    public function getSrcDirectory(): string
    {
        $v = ArrayHelper::getValue($this->data, ['extra', 'spaceonfire2warp', 'directory']);
        if (null !== $v) {
            return joinPath(\dirname($this->filename), $v);
        }

        $v = ArrayHelper::getValue($this->data, ['autoload', 'psr-4'], []);
        if ([] !== $v) {
            [$directory] = (array)\array_values($v)[0] + [null];

            if (\is_string($directory)) {
                return joinPath(\dirname($this->filename), $directory);
            }
        }

        return \dirname($this->filename);
    }

    public function getAutoloaderFilename(): string
    {
        $vendor = ArrayHelper::getValue($this->data, ['config', 'vendor-dir'], 'vendor');
        return joinPath(\dirname($this->filename), $vendor, 'autoload.php');
    }

    public function platformCheckEnabled(): bool
    {
        $check = ArrayHelper::getValue($this->data, ['config', 'platform-check'], 'php-only');
        return false !== $check;
    }

    /**
     * @return Promise<self>
     */
    public static function load(string $filename): Promise
    {
        return call(static function () use ($filename) {
            $fs = filesystem();

            try {
                $filename = yield self::findFile($filename);
                $data = \json_decode(yield $fs->read($filename), true, 512, \JSON_THROW_ON_ERROR);

                return new self($filename, $data);
            } catch (\Throwable $e) {
                throw new \RuntimeException('Unable to find composer.json', 0, $e);
            }
        });
    }

    /**
     * @param string $filename
     * @return Promise<string>
     */
    private static function findFile(string $filename): Promise
    {
        return call(static function () use ($filename) {
            $fs = filesystem();

            $filename = absolutePath($filename);

            if ('composer.json' !== \basename($filename)) {
                $directory = (yield $fs->isDirectory($filename)) ? $filename : \dirname($filename);
                $filename = joinPath($directory, 'composer.json');
            }

            while (true) {
                if (yield $fs->exists($filename)) {
                    return $filename;
                }

                if ('/composer.json' === $filename) {
                    break;
                }

                $filename = joinPath(\dirname($filename, 2), 'composer.json');
            }

            throw new \RuntimeException('Unable to find composer.json');
        });
    }
}
