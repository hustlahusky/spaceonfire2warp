<?php

declare(strict_types=1);

namespace App\Rules\Criteria;

use App\Reflection;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeVisitorAbstract;
use Warp\Criteria\CriteriaInterface;
use Warp\Criteria\Expression\ExpressionFactory;

final class CriteriaExprReplaceVisitor extends NodeVisitorAbstract
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

        if (!$node->name instanceof Node\Identifier) {
            return null;
        }

        if (!$this->reflection->classImplements($node->class, CriteriaInterface::class)) {
            return null;
        }

        $methodName = $node->name->name;

        if ('expr' !== $methodName) {
            return null;
        }

        return new StaticCall(new FullyQualified(ExpressionFactory::class), 'new');
    }
}
