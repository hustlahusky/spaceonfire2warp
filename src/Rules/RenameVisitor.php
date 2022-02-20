<?php

declare(strict_types=1);

namespace App\Rules;

use App\Reflection;
use App\Rules\Replacement\PipelineReplacement;
use App\Rules\Replacement\RegexReplacement;
use App\Rules\Replacement\ReplacementInterface;
use App\Rules\Replacement\VendorReplacement;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class RenameVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private readonly ReplacementInterface $replacement,
        private readonly Reflection $reflection,
    ) {
    }

    public static function v2(): ReplacementInterface
    {
        return new VendorReplacement();
    }

    public static function v3(): ReplacementInterface
    {
        return new PipelineReplacement(
            new VendorReplacement(),

            // getwarp/clock
            new RegexReplacement(
                '#^Warp\\\\ValueObject\\\\Date#',
                'Warp\\Clock',
            ),

            // getwarp/command-bus
            new RegexReplacement(
                '#^Warp\\\\CommandBus\\\\Bridge\\\\PsrLog#',
                'Warp\\CommandBus\\Middleware\\Logger',
            ),
            new RegexReplacement(
                '#^Warp\\\\CommandBus\\\\Bridge\\\\SymfonyStopwatch#',
                'Warp\\CommandBus\\Middleware\\Profiler',
            ),
            new RegexReplacement(
                '#^Warp\\\\CommandBus\\\\CanNotInvokeHandler$#',
                'Warp\\CommandBus\\Exception\\CannotInvokeHandlerException',
            ),
            new RegexReplacement(
                '#^Warp\\\\CommandBus\\\\Mapping\\\\CommandToHandlerMapping$#',
                'Warp\\CommandBus\\Mapping\\CommandToHandlerMappingInterface',
            ),
            new RegexReplacement(
                '#^Warp\\\\CommandBus\\\\Mapping\\\\FailedToMapCommand$#',
                'Warp\\CommandBus\\Exception\\FailedToMapCommandException',
            ),
            new RegexReplacement(
                '#^Warp\\\\CommandBus\\\\Middleware$#',
                'Warp\\CommandBus\\MiddlewareInterface',
            ),

            // getwarp/common
            new RegexReplacement(
                '#^Warp\\\\Collection\\\\ArrayHelper$#',
                'Warp\\Common\\ArrayHelper',
            ),

            // getwarp/container
            new RegexReplacement(
                '#^Warp\\\\Container\\\\Container$#',
                'Warp\\Container\\DefinitionContainer',
            ),
            new RegexReplacement(
                '#^Warp\\\\Container\\\\ContainerChain$#',
                'Warp\\Container\\CompositeContainer',
            ),
            new RegexReplacement(
                '#^Warp\\\\Container\\\\ContainerInterface$#',
                'Warp\\Container\\DefinitionAggregateInterface',
            ),
            new RegexReplacement(
                '#^Warp\\\\Container\\\\ContainerWithServiceProvidersInterface$#',
                'Warp\\Container\\ServiceProviderAggregateInterface',
            ),
            new RegexReplacement(
                '#^Warp\\\\Container\\\\Definition\\\\Definition$#',
                'Warp\\Container\\Factory\\Definition',
            ),
            new RegexReplacement(
                '#^Warp\\\\Container\\\\Definition\\\\DefinitionInterface$#',
                'Warp\\Container\\DefinitionInterface',
            ),
            new RegexReplacement(
                '#^Warp\\\\Container\\\\Definition\\\\DefinitionAggregateInterface$#',
                'Warp\\Container\\DefinitionAggregateInterface',
            ),
            new RegexReplacement(
                '#^Warp\\\\Container\\\\Definition\\\\DefinitionTag$#',
                'Warp\\Container\\Factory\\DefinitionTag',
            ),
            new RegexReplacement(
                '#^Warp\\\\Container\\\\Reflection\\\\ReflectionFactory$#',
                'Warp\\Container\\Factory\\Reflection\\ReflectionFactoryAggregate',
            ),
            new RegexReplacement(
                '#^Warp\\\\Container\\\\Reflection\\\\ReflectionInvoker$#',
                'Warp\\Container\\Factory\\Reflection\\ReflectionInvoker',
            ),
            new RegexReplacement(
                '#^Warp\\\\Container\\\\ReflectionContainer$#',
                'Warp\\Container\\FactoryContainer',
            ),
            new RegexReplacement(
                '#^Warp\\\\Container\\\\ServiceProvider\\\\ServiceProviderAggregateInterface$#',
                'Warp\\Container\\ServiceProviderAggregateInterface',
            ),

            // getwarp/cycle-bridge
            new RegexReplacement(
                '#^Warp\\\\DataSource\\\\(Adapters|Bridge)\\\\CycleOrm\\\\Repository\\\\AbstractCycleRepository$#',
                'Warp\\Bridge\\Cycle\\Repository\\AbstractRepository',
            ),
            new RegexReplacement(
                '#^Warp\\\\DataSource\\\\Bridge\\\\CycleOrm\\\\Repository\\\\AbstractCycleRepositoryAdapter$#',
                'Warp\\Bridge\\Cycle\\Repository\\AbstractRepository',
            ),
            new RegexReplacement(
                '#^Warp\\\\DataSource\\\\(Adapters|Bridge)\\\\CycleOrm\\\\Mapper\\\\BasicCycleMapper$#',
                'Warp\\Bridge\\Cycle\\Mapper\\HydratorMapper',
            ),
            new RegexReplacement(
                '#^Warp\\\\DataSource\\\\(Adapters|Bridge)\\\\CycleOrm\\\\Mapper\\\\UuidCycleMapper$#',
                'Warp\\Bridge\\Cycle\\Mapper\\HydratorMapper',
            ),
            new RegexReplacement(
                '#^Warp\\\\DataSource\\\\(Adapters|Bridge)\\\\CycleOrm\\\\Mapper\\\\StdClassCycleMapper$#',
                'Warp\\Bridge\\Cycle\\Mapper\\StdClassMapper',
            ),
            new RegexReplacement(
                '#^Warp\\\\DataSource\\\\(Adapters|Bridge)\\\\CycleOrm\\\\Query\\\\CycleQueryExpressionVisitor$#',
                'Warp\\Bridge\\Cycle\\Select\\CycleExpressionVisitor',
            ),
            new RegexReplacement(
                '#^Warp\\\\DataSource\\\\Bridge\\\\CycleOrm\\\\Schema\\\\AbstractRegistryFactory$#',
                'Warp\\Bridge\\Cycle\\Schema\\AbstractRegistryFactory',
            ),
            new RegexReplacement(
                '#^Warp\\\\Bridge\\\\Cycle\\\\Collection\\\\Onfire#',
                'Warp\\Bridge\\Cycle\\Collection\\Warp',
            ),

            // getwarp/data-source
            new RegexReplacement(
                '#^Warp\\\\DataSource\\\\Exceptions\\\\NotFoundException$#',
                'Warp\\DataSource\\EntityNotFoundException',
            ),
            new RegexReplacement(
                '#^Warp\\\\DataSource\\\\Exceptions\\\\DomainException$#',
                'DomainException',
            ),
            new RegexReplacement(
                '#^Warp\\\\DataSource\\\\Exceptions\\\\(RemoveException|SaveException)$#',
                'RuntimeException',
            ),
            new RegexReplacement(
                '#^Warp\\\\DataSource\\\\Query\\\\AbstractExpressionVisitor$#',
                'Warp\\DataSource\\AbstractExpressionVisitor',
            ),

            // getwarp/laminas-hydrator-bridge
            new RegexReplacement(
                '#^Warp\\\\ValueObject\\\\(Bridge|Integrations)\\\\LaminasHydrator\\\\BooleanStrategy$#',
                'Warp\\Bridge\\LaminasHydrator\\Strategy\\BooleanStrategy',
            ),
            new RegexReplacement(
                '#^Warp\\\\ValueObject\\\\(Bridge|Integrations)\\\\LaminasHydrator\\\\NullableStrategy$#',
                'Warp\\Bridge\\LaminasHydrator\\Strategy\\NullableStrategy',
            ),
            new RegexReplacement(
                '#^Warp\\\\ValueObject\\\\(Bridge|Integrations)\\\\(LaminasHydrator|HydratorStrategy)\\\\DateValue(LaminasHydrator|ZendHydrator)?Strategy$#',
                'Warp\\Bridge\\LaminasHydrator\\Strategy\\DateValueStrategy',
            ),
            new RegexReplacement(
                '#^Warp\\\\ValueObject\\\\(Bridge|Integrations)\\\\(LaminasHydrator|HydratorStrategy)\\\\ValueObject(LaminasHydrator|ZendHydrator)?Strategy$#',
                'Warp\\Bridge\\LaminasHydrator\\Strategy\\ValueObjectStrategy',
            ),
            new RegexReplacement(
                '#^Warp\\\\DataSource\\\\(Adapters|Bridge)\\\\CycleOrm\\\\Mapper\\\\Hydrator\\\\StdClassHydrator$#',
                'Warp\\Bridge\\LaminasHydrator\\StdClassHydrator',
            ),

            // getwarp/type
            new RegexReplacement(
                '#^Warp\\\\Type\\\\ConjunctionType$#',
                'Warp\\Type\\IntersectionType',
            ),
            new RegexReplacement(
                '#^Warp\\\\Type\\\\DisjunctionType$#',
                'Warp\\Type\\UnionType',
            ),
            new RegexReplacement(
                '#^Warp\\\\Type\\\\Factory\\\\ConjunctionTypeFactory$#',
                'Warp\\Type\\Factory\\IntersectionTypeFactory',
            ),
            new RegexReplacement(
                '#^Warp\\\\Type\\\\Factory\\\\DisjunctionTypeFactory$#',
                'Warp\\Type\\Factory\\UnionTypeFactory',
            ),
            new RegexReplacement(
                '#^Warp\\\\Type\\\\Factory\\\\CompositeTypeFactory$#',
                'Warp\\Type\\Factory\\TypeFactoryAggregate',
            ),
            new RegexReplacement(
                '#^Warp\\\\Type\\\\Cast\\\\DisjunctionCasterFactory$#',
                'Warp\\Type\\Factory\\UnionTypeFactory',
            ),

            // getwarp/value-object
            new RegexReplacement(
                '#^Warp\\\\ValueObject\\\\BaseValueObject$#',
                'Warp\\ValueObject\\AbstractValueObject',
            ),
            new RegexReplacement(
                '#^Warp\\\\ValueObject\\\\EnumValue$#',
                'Warp\\ValueObject\\AbstractEnumValue',
            ),
            new RegexReplacement(
                '#^Warp\\\\ValueObject\\\\IntValue$#',
                'Warp\\ValueObject\\AbstractIntValue',
            ),
            new RegexReplacement(
                '#^Warp\\\\ValueObject\\\\StringValue$#',
                'Warp\\ValueObject\\AbstractStringValue',
            ),
        );
    }

    public function leaveNode(Node $node): Node|null
    {
        if (!$node instanceof Node\Name) {
            return null;
        }

        $resolvedName = $this->getNameNode($node);
        if (null === $resolvedName) {
            return null;
        }

        $name = $resolvedName->toString();
        $modified = $this->replacement->replace($name);
        if ($modified === $name) {
            return null;
        }

        /** @var class-string<Node\Name> $class */
        $class = $resolvedName::class;
        $output = $modifiedName = new $class($modified, $node->getAttributes());

        if (1 === \count($node->parts) && \count($resolvedName->parts) === \count($modifiedName->parts)) {
            $class = $node::class;
            $output = new $class($modifiedName->getLast(), $node->getAttributes());
        }

        if ($output->hasAttribute('resolvedName')) {
            $output->setAttribute('resolvedName', $modifiedName);
        }
        if ($output->hasAttribute('namespacedName')) {
            $output->setAttribute('namespacedName', $modifiedName);
        }

        return $output;
    }

    private function getNameNode(Node\Name $node): Node\Name|null
    {
        $resolvedName = $node->getAttribute('resolvedName');
        if ($resolvedName instanceof Node\Name) {
            return $resolvedName;
        }

        $namespacedName = $node->getAttribute('namespacedName');
        if ($namespacedName instanceof Node\Name) {
            $parent = $node->getAttribute('parent');

            if ($parent instanceof Node\Expr\ConstFetch) {
                if (null !== $this->reflection->safeReflectConstant($namespacedName->toString())) {
                    return $namespacedName;
                }

                if (null !== $this->reflection->safeReflectConstant($node->toString())) {
                    return $node;
                }

                return null;
            }

            if ($parent instanceof Node\Expr\FuncCall) {
                if (null !== $this->reflection->safeReflectFunction($namespacedName->toString())) {
                    return $namespacedName;
                }

                if (null !== $this->reflection->safeReflectFunction($node->toString())) {
                    return $node;
                }

                return null;
            }

            return $namespacedName;
        }

        return $node;
    }
}
