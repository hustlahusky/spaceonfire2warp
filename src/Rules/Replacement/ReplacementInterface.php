<?php

declare(strict_types=1);

namespace App\Rules\Replacement;

interface ReplacementInterface
{
    public function replace(string $input): string;
}
