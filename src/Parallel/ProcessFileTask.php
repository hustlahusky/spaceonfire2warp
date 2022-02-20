<?php

declare(strict_types=1);

namespace App\Parallel;

use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use App\Parser;
use App\Parser\PhpFile;
use App\Version;

final class ProcessFileTask implements Task
{
    public function __construct(
        public readonly string $file,
        public readonly string $autoloaderFilename,
        public readonly Version $version = Version::V3,
    ) {
    }

    public function run(Environment $environment): \Generator
    {
        $projectAutoloader = @include $this->autoloaderFilename;
        if (!$projectAutoloader) {
            throw new \RuntimeException('Unable to include project autoloader.');
        }

        $parser = Parser::make($this->version);

        /** @var PhpFile $file */
        $file = yield $parser->loadFile($this->file);
        $file->transform($parser);
        yield $file->save($parser);

        $parser->reset();
    }
}
