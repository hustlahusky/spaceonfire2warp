<?php

declare(strict_types=1);

namespace App\Rules\Type;

use App\Reflection;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\NodeVisitorAbstract;
use Warp\Type\BuiltinType;

final class BuiltinTypeConstructorArgumentsVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly Reflection $reflection,
    ) {
    }

    public function leaveNode(Node $node): StaticCall|null
    {
        if (!$node instanceof StaticCall) {
            return null;
        }

        if (!$node->class instanceof Node\Name) {
            return null;
        }

        if (BuiltinType::class !== $this->reflection->getClassName($node->class)) {
            return null;
        }

        if (!$node->name instanceof Node\Identifier || 'new' !== $node->name->name) {
            return null;
        }

        if (1 < \count($node->args)) {
            $node->args = [$node->args[0]];
            return $node;
        }

        return null;
    }
}
