<?php

declare(strict_types=1);

namespace App\Rules\Common;

use App\Reflection;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeVisitorAbstract;

final class ArrayHelperReplaceWithYiiVisitor extends NodeVisitorAbstract
{
    private const METHOD_REPLACEMENTS = [
        'toArray' => true,
        'merge' => true,
        'getValue' => true,
        'getValueByPath' => true,
        'setValue' => true,
        'setValueByPath' => true,
        'remove' => true,
        'removeByPath' => true,
        'removeValue' => true,
        'index' => true,
        'getColumn' => true,
        'map' => true,
        'keyExists' => true,
        'pathExists' => true,
        'htmlEncode' => true,
        'htmlDecode' => true,
        'isAssociative' => true,
        'isIndexed' => true,
        'isIn' => true,
        'isSubset' => true,
        'filter' => true,
        'getObjectVars' => true,
    ];

    public function __construct(
        private readonly Reflection $reflection,
    ) {
    }

    public function leaveNode(Node $node): StaticCall|null
    {
        if (!$node instanceof StaticCall) {
            return null;
        }

        if (!$node->class instanceof Node\Name || $node->class->isSpecialClassName()) {
            return null;
        }

        if (!$node->name instanceof Node\Identifier) {
            return null;
        }

        if (!$this->reflection->classExtends($node->class, 'Warp\\Common\\ArrayHelper')) {
            return null;
        }

        $methodName = $node->name->name;
        if (!isset(self::METHOD_REPLACEMENTS[$methodName])) {
            return null;
        }

        return new StaticCall(new FullyQualified('Yiisoft\\Arrays\\ArrayHelper'), $methodName, $node->args);
    }
}
