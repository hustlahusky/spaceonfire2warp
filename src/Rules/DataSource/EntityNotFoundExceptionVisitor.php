<?php

declare(strict_types=1);

namespace App\Rules\DataSource;

use App\Reflection;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;
use Warp\DataSource\EntityNotFoundException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class EntityNotFoundExceptionVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly Reflection $reflection,
    ) {
    }

    public function leaveNode(Node $node): Node|null
    {
        if (!$node instanceof Class_) {
            return null;
        }

        if (!$this->reflection->classExtends($node, EntityNotFoundException::class)) {
            return null;
        }

        if (null === $node->getMethod('__construct')) {
            $node->stmts = \iterator_to_array($this->addLegacyConstructor($node), false);
        }

        $node->stmts = $this->removeFriendlyExceptionMethods($node->stmts);
        $node->implements = \array_filter(
            $node->implements,
            fn (Node\Name $n) => FriendlyExceptionInterface::class !== $this->reflection->getClassName($n),
        );

        return $node;
    }

    /**
     * @param Node\Stmt[] $stmts
     * @return Node\Stmt[]
     */
    private function removeFriendlyExceptionMethods(array $stmts): array
    {
        return \array_filter($stmts, static function ($node) {
            if (!$node instanceof ClassMethod) {
                return true;
            }

            $methodName = $node->name->name;

            return 'getName' !== $methodName && 'getSolution' !== $methodName;
        });
    }

    private function addLegacyConstructor(Class_ $node): \Generator
    {
        $legacy = $this->generateClassWithLegacyConstructor();

        $constructor = $legacy->getMethod('__construct');
        \assert(null !== $constructor);
        $getDefaultMessage = $legacy->getMethod('getDefaultMessage');
        \assert(null !== $getDefaultMessage);

        if (null !== $v = $node->getMethod('getDefaultMessage')) {
            $comment = $getDefaultMessage->getDocComment();
            $getDefaultMessage = $v;
            if (null !== $comment) {
                $getDefaultMessage->setDocComment($comment);
            }
        }

        $prepareParameters = $legacy->getMethod('prepareParameters');
        \assert(null !== $prepareParameters);

        $done = false;

        foreach ($node->stmts as $stmt) {
            if (!$done && $stmt instanceof ClassMethod) {
                yield $constructor;
                yield $getDefaultMessage;
                yield $prepareParameters;
                $done = true;
            }

            if ($stmt instanceof ClassMethod && 'getDefaultMessage' === $stmt->name->name) {
                continue;
            }

            yield $stmt;
        }

        if (!$done) {
            yield $constructor;
            yield $getDefaultMessage;
            yield $prepareParameters;
        }
    }

    private function generateClassWithLegacyConstructor(): Class_
    {
        static $legacy = null;

        if (null !== $legacy) {
            return $legacy;
        }

        $parsed = $this->reflection->parse(
            '<?php' . \PHP_EOL . <<<EOF
class MyDomainException extends \\DomainException
{
    /**
     * TODO: make constructor compatible with parent one, enable translation support
     */
    public function __construct(
        ?string \$message = null,
        array \$parameters = [],
        int \$code = 0,
        ?\\Throwable \$previous = null
    ) {
        \$message = \$message ?? \$this->getDefaultMessage(\$this->prepareParameters(\$parameters));
        \$message = \\str_replace(\\array_keys(\$parameters), \\array_values(\$parameters), \$message);
        parent::__construct(\$message, \$code, \$previous);
    }

    /**
     * @deprecated
     */
    protected function getDefaultMessage(array \$parameters = []): string
    {
        return self::getDefaultName();
    }

    /**
     * @deprecated
     */
    protected function prepareParameters(array \$parameters): array
    {
        \$keys = \\array_map(static function (\$key) {
            return '{' . \\trim(\$key, '{}') . '}';
        }, \\array_keys(\$parameters));

        return \\array_combine(\$keys, \$parameters) ?: [];
    }
}
EOF
        );

        $legacy = $parsed[0];

        \assert($legacy instanceof Class_);

        return $legacy;
    }
}
