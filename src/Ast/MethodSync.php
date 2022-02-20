<?php

declare(strict_types=1);

namespace App\Ast;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;

final class MethodSync
{
    public const FLAGS = 1;

    public const PARAMS = 2;

    public const RETURN = 4;

    private const METHOD_MODIFIERS = Class_::MODIFIER_PUBLIC | Class_::MODIFIER_PROTECTED | Class_::MODIFIER_PRIVATE | Class_::MODIFIER_STATIC | Class_::MODIFIER_ABSTRACT | Class_::MODIFIER_FINAL;

    private bool $modified = false;

    public function __construct(
        private readonly ClassMethod $implementation,
        private readonly ClassMethod $contract,
        private readonly PrettyPrinterAbstract $printer = new Standard(),
    ) {
    }

    public function isModified(): bool
    {
        return $this->modified;
    }

    public function flags(): void
    {
        $implementationFlags = $this->implementation->flags & self::METHOD_MODIFIERS ^ Class_::MODIFIER_FINAL ^ Class_::MODIFIER_ABSTRACT;
        $contractFlags = $this->contract->flags & self::METHOD_MODIFIERS ^ Class_::MODIFIER_FINAL ^ Class_::MODIFIER_ABSTRACT;

        if ($implementationFlags === $contractFlags) {
            return;
        }

        $this->modified = true;

        $visibility = ($this->implementation->flags & (Class_::MODIFIER_PUBLIC | Class_::MODIFIER_PROTECTED | Class_::MODIFIER_PRIVATE));
        if (0 === $visibility) {
            $visibility = ($this->contract->flags & (Class_::MODIFIER_PUBLIC | Class_::MODIFIER_PROTECTED | Class_::MODIFIER_PRIVATE));
        }
        $static = $this->contract->flags & Class_::MODIFIER_STATIC;

        $this->implementation->flags = $visibility | $static | ($this->implementation->flags & (Class_::MODIFIER_FINAL | Class_::MODIFIER_ABSTRACT));
    }

    public function params(): void
    {
        $this->modified = true;
        $this->implementation->params = $this->contract->params;
    }

    public function returnType(): void
    {
        $implementation = $this->print($this->implementation->returnType) ?? '1';
        $contract = $this->print($this->contract->returnType) ?? '0';

        if ($implementation === $contract) {
            return;
        }

        $this->modified = true;
        $this->implementation->returnType = $this->contract->returnType;
    }

    public function get(): ClassMethod|null
    {
        if ($this->modified) {
            return $this->implementation;
        }

        return null;
    }

    public function run(int $options = self::FLAGS | self::PARAMS | self::RETURN): ClassMethod|null
    {
        if ($options & self::FLAGS) {
            $this->flags();
        }

        if ($options & self::PARAMS) {
            $this->params();
        }

        if ($options & self::RETURN) {
            $this->returnType();
        }

        return $this->get();
    }

    private function print(mixed $node): string|null
    {
        if ($node instanceof Node) {
            return $this->printer->prettyPrint([$node]);
        }

        return null;
    }
}
