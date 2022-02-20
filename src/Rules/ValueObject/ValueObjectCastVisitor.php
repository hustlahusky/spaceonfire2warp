<?php

declare(strict_types=1);

namespace App\Rules\ValueObject;

use App\Ast\MethodSync;
use App\Reflection;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;
use Warp\Exception\PackageMissingException;
use Warp\ValueObject\AbstractValueObject;

final class ValueObjectCastVisitor extends NodeVisitorAbstract
{
    private const CAST = 'cast';

    public function __construct(
        private readonly Reflection $reflection,
    ) {
    }

    public function leaveNode(Node $node): Node|null
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        if (self::CAST !== $node->name->name) {
            return null;
        }

        $parent = $node->getAttribute('parent');
        if (!$parent instanceof Class_) {
            return null;
        }

        if (!$this->reflection->classExtends($parent, AbstractValueObject::class)) {
            return null;
        }

        $method = $parent->getMethod(self::CAST);
        if (null === $method) {
            throw PackageMissingException::new('getwarp/value-object', '^3.0');
        }

        return (new MethodSync(clone $node, $method))->run(MethodSync::FLAGS);
    }
}
