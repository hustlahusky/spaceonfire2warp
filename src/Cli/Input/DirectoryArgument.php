<?php

declare(strict_types=1);

namespace App\Cli\Input;

/**
 * @extends InputArgument<string|null>
 */
final class DirectoryArgument extends InputArgument
{
    public function __construct(
        string $name = 'directory',
        string $description = 'Specify directory to find files in',
        string|null $default = null,
    ) {
        parent::__construct($name, self::OPTIONAL, $description, $default);
    }
}
