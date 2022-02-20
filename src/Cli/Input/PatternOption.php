<?php

declare(strict_types=1);

namespace App\Cli\Input;

/**
 * @extends InputOption<string>
 */
final class PatternOption extends InputOption
{
    public function __construct(
        string $name = 'pattern',
        string $description = 'Filename pattern',
        string $default = '/.php$/',
    ) {
        parent::__construct(
            name: $name,
            mode: self::VALUE_REQUIRED,
            description: $description,
            default: $default,
        );
    }
}
