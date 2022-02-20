<?php

declare(strict_types=1);

namespace App\Cli;

use Amp\Producer;
use App\Cli;
use App\Composer;
use App\Finder;
use App\Parallel\ProcessFileTask;
use App\Parallel\TaskScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Amp\call;
use function Amp\File\filesystem;
use function Amp\Parallel\Worker\pool;
use function Amp\Promise\wait;
use function App\Filesystem\absolutePath;

final class MainCommand extends Command
{
    private const DEFAULT_NAME = 'main';

    protected static $defaultName = self::DEFAULT_NAME;

    private readonly Input\DirectoryArgument $directory;

    private readonly Input\PatternOption $pattern;

    private readonly Input\VersionOption $version;

    public function __construct(string|null $name = null)
    {
        parent::__construct($name);

        $this->directory = new Input\DirectoryArgument();
        $this->directory->register($this);

        $this->pattern = new Input\PatternOption();
        $this->pattern->register($this);

        $this->version = new Input\VersionOption();
        $this->version->register($this);
    }

    public function getName(): string
    {
        return parent::getName() ?? self::DEFAULT_NAME;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exitCode = self::SUCCESS;
        $output = Cli::style($input, $output);

        wait(call(static function (self $that) use ($input, $output, &$exitCode) {
            $pool = pool();
            $fs = filesystem();

            $directory = $that->directory->getValueFrom($input);
            /** @var Composer $composer */
            $composer = yield Composer::load($directory ?? '.');
            $directory = null === $directory ? $composer->getSrcDirectory() : absolutePath($directory);

            $pattern = $that->pattern->getValueFrom($input);

            $version = $that->version->getValueFrom($input);

            if ($composer->platformCheckEnabled()) {
                $output->warning([
                    'Platform check is enabled in your composer.json. Try to disable it, if you face any issues due to the autoloader include.',
                    'More info: https://getcomposer.org/doc/06-config.md#platform-check',
                ]);
            }

            try {
                $finder = Finder::new($fs, $directory, $pattern);
                $progress = $output->createProgressBar();
                $progress->start();
                $scheduler = new TaskScheduler(
                    $pool,
                    new Producer(static function (callable $emit) use ($finder, $progress, $composer, $version) {
                        while (yield $finder->advance()) {
                            $progress->advance();
                            yield $emit(
                                new ProcessFileTask(
                                    $finder->getCurrent(),
                                    $composer->getAutoloaderFilename(),
                                    $version,
                                ),
                            );
                        }
                    }),
                );
                yield $scheduler->run();
                $progress->finish();
                $output->writeln('');
                $output->success([
                    'Done. Make sure to update your dependencies in composer.json',
                ]);
            } finally {
                $exitCode = yield $pool->shutdown();
            }
        }, $this));

        return \max(self::SUCCESS, ...(array)$exitCode);
    }
}
