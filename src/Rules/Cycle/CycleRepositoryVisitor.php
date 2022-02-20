<?php

declare(strict_types=1);

namespace App\Rules\Cycle;

use App\Reflection;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;
use Warp\Bridge\Cycle\Repository\AbstractRepository;

final class CycleRepositoryVisitor extends NodeVisitorAbstract
{
    private const REMOVE_METHODS = [
        'makeNotFoundException',
        'makeRemoveException',
        'makeSaveException',
    ];

    public function __construct(
        private readonly Reflection $reflection,
    ) {
    }

    public function leaveNode(Node $node): Node|null
    {
        if (!$node instanceof Class_) {
            return null;
        }

        if (!$this->reflection->classExtends($node, AbstractRepository::class)) {
            return null;
        }

        $node->stmts = \array_filter($node->stmts, static function ($node) {
            if (!$node instanceof Node\Stmt\ClassMethod) {
                return true;
            }

            return !\in_array($node->name->name, self::REMOVE_METHODS, true);
        });

        return $node;
    }
}
