<?php

declare(strict_types=1);

namespace App\Cli\Input;

use App\Version;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class VersionOption
{
    public function __construct(
        private readonly InputOption $v2 = new InputOption(
            name: 'v2',
            mode: InputOption::VALUE_NONE,
            description: 'Update only vendor namespace',
        ),
        private readonly InputOption $v3 = new InputOption(
            name: 'v3',
            mode: InputOption::VALUE_NONE,
            description: 'Use all rules to upgrade codebase up to v3',
        ),
        private readonly Version $default = Version::V3,
    ) {
    }

    public function register(Command $command): void
    {
        $command->getDefinition()->addOption($this->v2);
        $command->getDefinition()->addOption($this->v3);
    }

    public function getValueFrom(InputInterface $input): Version
    {
        if ($input->getOption($this->v3->getName())) {
            return Version::V3;
        }

        if ($input->getOption($this->v2->getName())) {
            return Version::V2;
        }

        return $this->default;
    }
}
