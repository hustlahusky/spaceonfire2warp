<?php

declare(strict_types=1);

namespace App\Parser;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

final class CloningVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node): Node
    {
        return clone $node;
    }

    /**
     * @param Node[] $ast
     * @return Node[]
     */
    public static function cloneTree(array $ast): array
    {
        $t = new NodeTraverser();
        $t->addVisitor(new self());

        return $t->traverse($ast);
    }
}
