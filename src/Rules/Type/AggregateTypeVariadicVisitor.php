<?php

declare(strict_types=1);

namespace App\Rules\Type;

use App\Reflection;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\NodeVisitorAbstract;
use Warp\Type\IntersectionType;
use Warp\Type\UnionType;

final class AggregateTypeVariadicVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly Reflection $reflection,
    ) {
    }

    public function leaveNode(Node $node): StaticCall|Node|null
    {
        if (!$node instanceof StaticCall) {
            return null;
        }

        if (!$node->class instanceof Node\Name) {
            return null;
        }

        $class = $this->reflection->getClassName($node->class);

        if (UnionType::class !== $class && IntersectionType::class !== $class) {
            return null;
        }

        if (!$node->name instanceof Node\Identifier || 'new' !== $node->name->name) {
            return null;
        }

        if (1 !== \count($node->args) || $node->isFirstClassCallable()) {
            return null;
        }

        $firstArg = $node->args[0] ?? null;
        if (!$firstArg instanceof Node\Arg) {
            return null;
        }

        $firstArgValue = $firstArg->value;
        if (!$firstArgValue instanceof Node\Expr\Array_) {
            return null;
        }

        $args = [];
        foreach ($firstArgValue->items as $item) {
            if ($item instanceof Node\Expr\ArrayItem) {
                $args[] = new Node\Arg($item->value);
            }
        }
        $node->args = $args;
        return $node;
    }
}
