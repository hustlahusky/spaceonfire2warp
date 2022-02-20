<?php

declare(strict_types=1);

namespace App\Rules\DataSource;

use App\Ast\MethodSync;
use App\Reflection;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeVisitorAbstract;
use Warp\DataSource\RepositoryInterface;

final class RepositoryInterfaceVisitor extends NodeVisitorAbstract
{
    private ClassLike|null $repository = null;

    public function __construct(
        private readonly Reflection $reflection,
    ) {
    }

    public function enterNode(Node $node): Node|null
    {
        if (!$node instanceof Interface_ && !$node instanceof Class_) {
            return null;
        }

        if (!$this->reflection->classImplements($node, RepositoryInterface::class)) {
            return null;
        }

        $this->repository = $node;

        return null;
    }

    public function leaveNode(Node $node): Node|null
    {
        if ($node === $this->repository) {
            $this->repository = null;
        }

        if (null !== $this->repository) {
            return $this->handleNode($node);
        }

        return null;
    }

    private function handleNode(Node $node): Node|null
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        $methodName = $node->name->name;

        if (!\in_array($methodName, ['save', 'remove', 'findByPrimary'], true)) {
            return null;
        }

        $target = $this->reflection->reflectClass(RepositoryInterface::class)->getMethod($methodName)->getAst();
        return (new MethodSync(clone $node, $target))->run();
    }
}
