<?php

declare(strict_types=1);

namespace App\Rules\Replacement;

final class NoopReplacement implements ReplacementInterface
{
    public function replace(string $input): string
    {
        return $input;
    }
}
