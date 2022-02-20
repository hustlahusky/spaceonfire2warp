<?php

declare(strict_types=1);

namespace App\Rules\Common;

use App\Reflection;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\NodeVisitorAbstract;
use Warp\Common\Factory\StaticConstructorInterface;

final class StaticConstructorVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly Reflection $reflection,
    ) {
    }

    public function leaveNode(Node $node): ?Node
    {
        if (!$node instanceof New_) {
            return null;
        }

        if (!$node->class instanceof Node\Name) {
            return null;
        }

        if (!$this->reflection->classImplements($node->class, StaticConstructorInterface::class)) {
            return null;
        }

        return new StaticCall($node->class, 'new', $node->args);
    }
}
