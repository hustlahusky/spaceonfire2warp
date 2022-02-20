<?php

declare(strict_types=1);

namespace App;

use App\Rules\Replacement\NoopReplacement;
use App\Rules\Replacement\ReplacementInterface;
use PhpParser\ErrorHandler;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @internal
 */
final class Reflection implements Reflector
{
    private readonly BetterReflection $reflection;

    public function __construct(
        private readonly \PhpParser\Parser $parser,
        private readonly ErrorHandler $errorHandler,
        private readonly ReplacementInterface $replacement = new NoopReplacement(),
    ) {
        $reflection = new BetterReflection();
        (\Closure::bind(static fn () => $reflection->phpParser = $parser, null, BetterReflection::class))();
        $this->reflection = $reflection;
    }

    /**
     * @return Stmt[]
     */
    public function parse(string $code): array
    {
        return $this->parser->parse($code, $this->errorHandler) ?? [];
    }

    public function classExtends(string|Name|Class_|Interface_ $class, string $parent): bool
    {
        $parent = $this->getClassName($parent);

        foreach ($this->getClassParents($class) as $classParent) {
            if ($parent === $classParent) {
                return true;
            }
        }

        return false;
    }

    public function classImplements(string|Name|Class_|Interface_ $class, string $parent): bool
    {
        $parent = $this->getClassName($parent);

        foreach ($this->getClassParents($class, true) as $classParent) {
            if ($parent === $classParent) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Generator<string>
     */
    public function getClassParents(string|Name|Class_|Interface_ $class, bool $interfaces = false): \Generator
    {
        try {
            $node = $this->getClassLike($class);
        } catch (\RuntimeException) {
            yield from [];
            return;
        }

        if ($node instanceof Class_) {
            if (null !== $node->extends) {
                $parentClass = $this->getClassName($node->extends);

                yield $parentClass;
                yield from $this->getClassParents($parentClass, $interfaces);
            }

            if (!$interfaces) {
                return;
            }

            foreach ($node->implements as $implement) {
                $parentClass = $this->getClassName($implement->toString());

                yield $parentClass;
                yield from $this->getClassParents($parentClass, $interfaces);
            }
        }

        if ($node instanceof Interface_) {
            foreach ($node->extends as $extend) {
                $parentClass = $this->getClassName($extend->toString());

                yield $parentClass;
                yield from $this->getClassParents($parentClass, $interfaces);
            }
        }
    }

    public function getClassName(string|Name $class): string
    {
        if ($class instanceof Name) {
            $class = self::nameToString($class);
        }

        return $this->replacement->replace($class);
    }

    public function reflectClass(string $identifierName): ReflectionClass
    {
        return $this->reflection->reflector()->reflectClass($identifierName);
    }

    public function reflectAllClasses(): iterable
    {
        return $this->reflection->reflector()->reflectAllClasses();
    }

    public function reflectFunction(string $identifierName): ReflectionFunction
    {
        return $this->reflection->reflector()->reflectFunction($identifierName);
    }

    public function reflectAllFunctions(): iterable
    {
        return $this->reflection->reflector()->reflectAllFunctions();
    }

    public function reflectConstant(string $identifierName): ReflectionConstant
    {
        return $this->reflection->reflector()->reflectConstant($identifierName);
    }

    public function reflectAllConstants(): iterable
    {
        return $this->reflection->reflector()->reflectAllConstants();
    }

    public function safeReflectClass(string $identifierName): ReflectionClass|null
    {
        try {
            return $this->reflectClass($identifierName);
        } catch (IdentifierNotFound) {
            return null;
        }
    }

    public function safeReflectFunction(string $identifierName): ReflectionFunction|null
    {
        try {
            return $this->reflectFunction($identifierName);
        } catch (IdentifierNotFound) {
            return null;
        }
    }

    public function safeReflectConstant(string $identifierName): ReflectionConstant|null
    {
        try {
            return $this->reflectConstant($identifierName);
        } catch (IdentifierNotFound) {
            return null;
        }
    }

    private function getClassLike(mixed $class): Stmt\ClassLike
    {
        if ($class instanceof Stmt\ClassLike) {
            return $class;
        }

        if (\is_string($class) || $class instanceof Name) {
            return $this->reflectClass($this->getClassName($class))->getAst();
        }

        throw new \RuntimeException(\sprintf(
            'Unable to detect class-like structure from input: %s',
            \get_debug_type($class),
        ));
    }

    private static function nameToString(Node\Name $node): string
    {
        $resolvedName = $node->getAttribute('resolvedName');
        if ($resolvedName instanceof Node\Name) {
            return $resolvedName->toString();
        }

        $namespacedName = $node->getAttribute('namespacedName');
        if ($namespacedName instanceof Node\Name) {
            return $namespacedName->toString();
        }

        return $node->toString();
    }
}
