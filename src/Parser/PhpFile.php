<?php

declare(strict_types=1);

namespace App\Parser;

use Amp\Promise;
use Amp\Success;
use App\Parser;
use PhpParser\Node;
use function Amp\call;
use function Amp\File\filesystem;

final class PhpFile
{
    /**
     * @var Node[]
     */
    private array $transformedAst;

    private bool $modified = false;

    /**
     * @param Node[] $originalAst
     * @param mixed[] $originalTokens
     */
    public function __construct(
        public readonly string $filename,
        private readonly array $originalAst,
        private readonly array $originalTokens,
    ) {
        $this->transformedAst = CloningVisitor::cloneTree($this->originalAst);
    }

    public function transform(Parser $parser): void
    {
        $this->transformedAst = $parser->transform($this->transformedAst);
        $left = $parser->print($this->originalAst);
        $right = $parser->print($this->transformedAst);
        $this->modified = $left !== $right;
    }

    /**
     * @return Promise<void>
     */
    public function save(Parser $parser): Promise
    {
        return call(function () use ($parser) {
            if (!$this->modified) {
                return new Success();
            }

            return filesystem()->write(
                $this->filename,
                $parser->printFormatPreserving($this->transformedAst, $this->originalAst, $this->originalTokens),
            );
        });
    }
}
