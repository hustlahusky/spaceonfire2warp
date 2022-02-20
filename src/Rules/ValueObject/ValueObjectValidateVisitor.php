<?php

declare(strict_types=1);

namespace App\Rules\ValueObject;

use App\Ast\MethodSync;
use App\Reflection;
use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Warp\Exception\PackageMissingException;
use Warp\ValueObject\AbstractValueObject;

final class ValueObjectValidateVisitor extends NodeVisitorAbstract
{
    private const VALIDATE = 'validate';

    public function __construct(
        private readonly Reflection $reflection,
    ) {
    }

    public function leaveNode(Node $node): Node|null
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        if (self::VALIDATE !== $node->name->name) {
            return null;
        }

        $parent = $node->getAttribute('parent');
        if (!$parent instanceof Class_) {
            return null;
        }

        if (!$this->reflection->classExtends($parent, AbstractValueObject::class)) {
            return null;
        }

        $output = clone $node;
        $method = $parent->getMethod(self::VALIDATE);
        if (null === $method) {
            throw PackageMissingException::new('getwarp/value-object', '^3.0');
        }
        $sync = new MethodSync($output, $method);
        $sync->run();

        if (!($this->fixVoidReturns($output) || $sync->isModified())) {
            return null;
        }

        $comments = $output->getComments();
        $comments[] = new Comment(
            '// TODO: move parent::validate() call from expressions, because this method is void now.'
        );
        $output->setAttribute('comments', $comments);

        return $output;
    }

    private function fixVoidReturns(ClassMethod $method): bool
    {
        $visitor = new class() extends NodeVisitorAbstract {
            public bool $modified = false;

            public function __construct(
                private readonly PrettyPrinterAbstract $printer = new Standard(),
            ) {
            }

            public function leaveNode(Node $node): Node|null
            {
                if (!$node instanceof Return_) {
                    return null;
                }

                $expr = $node->expr;

                if (null === $expr) {
                    return null;
                }

                $this->modified = true;

                if ($expr instanceof ConstFetch) {
                    $value = $expr->name->toString();

                    if ('true' === $value) {
                        return new Return_();
                    }

                    if ('false' === $value) {
                        $output = new Expression(new Throw_(new New_(new FullyQualified(
                            \InvalidArgumentException::class
                        ))));
                        $output->setAttribute('comments', [new Comment('// TODO: Specify validation error message')]);
                        return $output;
                    }
                }

                $oldReturn = \trim($this->printer->prettyPrint([$node]));
                $oldReturn = \str_contains($oldReturn, "\n") ? "/*\n" . $oldReturn . "\n*/" : '// ' . $oldReturn;
                $output = new Return_();
                $output->setAttribute('comments', [new Comment($oldReturn)]);
                return $output;
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);

        /** @var Node\Stmt[] $stmts */
        $stmts = $traverser->traverse($method->stmts ?? []);

        if (!$visitor->modified) {
            return false;
        }

        $method->stmts = $stmts;
        return true;
    }
}
