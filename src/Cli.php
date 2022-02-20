<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class Cli
{
    private const NAME = 'spaceonfire2warp';

    private const VERSION = '1.0.0';

    public static function application(): Application
    {
        $app = new Application(self::NAME, self::VERSION);
        $app->setCatchExceptions(false);
        $app->setAutoExit(false);

        $main = new Cli\MainCommand();
        $app->add($main);
        $app->setDefaultCommand($main->getName(), true);

        return $app;
    }

    public static function style(InputInterface $input, OutputInterface $output): SymfonyStyle
    {
        if ($output instanceof SymfonyStyle) {
            return $output;
        }

        return new SymfonyStyle($input, $output);
    }
}
