<?php

declare(strict_types=1);

namespace App\Rules\ValueObject;

use App\Reflection;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;
use Warp\ValueObject\AbstractValueObject;

final class ValueObjectNewSelfVisitor extends NodeVisitorAbstract
{
    private Class_|null $currentClass = null;

    public function __construct(
        private readonly Reflection $reflection,
    ) {
    }

    public function enterNode(Node $node): Node|null
    {
        if (!$node instanceof Class_) {
            return null;
        }

        if (!$this->reflection->classExtends($node, AbstractValueObject::class)) {
            return null;
        }

        $this->currentClass = $node;

        return null;
    }

    public function leaveNode(Node $node): Node|null
    {
        if ($node === $this->currentClass) {
            $this->currentClass = null;
        }

        if (null !== $this->currentClass) {
            return $this->handleNode($node);
        }

        return null;
    }

    private function handleNode(Node $node): Node|null
    {
        if (!$node instanceof New_) {
            return null;
        }

        if (!$node->class instanceof Node\Name) {
            return null;
        }

        $class = $node->class->toString();

        if ('self' !== $class && 'static' !== $class) {
            return null;
        }

        return new StaticCall(new Node\Name($class), 'new', $node->args);
    }
}
