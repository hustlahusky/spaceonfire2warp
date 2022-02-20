<?php

declare(strict_types=1);

namespace App\Rules\Replacement;

final class PipelineReplacement implements ReplacementInterface
{
    /**
     * @var ReplacementInterface[]
     */
    private array $replacements = [];

    public function __construct(
        ReplacementInterface $first,
        ReplacementInterface ...$rest,
    ) {
        $this->replacements[] = $first;
        foreach ($rest as $item) {
            $this->replacements[] = $item;
        }
    }

    public function replace(string $input): string
    {
        return \array_reduce(
            $this->replacements,
            static fn (string $i, ReplacementInterface $r) => $r->replace($i),
            $input,
        );
    }
}
