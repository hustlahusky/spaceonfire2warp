<?php

declare(strict_types=1);

namespace App\Rules\Replacement;

final class RegexReplacement implements ReplacementInterface
{
    public function __construct(
        public readonly string $pattern,
        public readonly string $replacement,
        public readonly int $limit = -1,
    ) {
    }

    public function replace(string $input): string
    {
        $output = \preg_replace($this->pattern, $this->replacement, $input, $this->limit);

        if (!\is_string($output)) {
            throw new \RuntimeException('Problem in pattern: ' . $this->pattern);
        }

        return $output;
    }
}
