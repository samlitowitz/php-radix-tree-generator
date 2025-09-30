<?php

namespace PhpRadixTreeGenerator\RadixTree;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;
use PhpRadixTreeGenerator\App\Console\Commands\Generate\Target;

final class NodeVisitor extends NodeVisitorAbstract
{
    public function __construct(
        private Target $target,
        private Array_ $rootExpr,
        private int $count
    ) {
    }

    public function leaveNode(Node $node)
    {
        switch (true) {
            case $node instanceof Namespace_:
                $node->name = new Name($this->target->namespace);
                break;
            case $node instanceof Class_:
                $node->name = new Node\Identifier($this->target->className);
                break;
            case $node instanceof Node\Stmt\Property:
                if (!$node->isPrivate()) {
                    break;
                }
                if (!$node->isStatic()) {
                    break;
                }
                foreach ($node->props as $prop) {
                    if (!($prop instanceof Node\PropertyItem)) {
                        continue;
                    }
                    if (!($prop->name instanceof Node\VarLikeIdentifier)) {
                        continue;
                    }
                    switch ($prop->name->name) {
                        case 'count':
                            $prop->default = new Node\Scalar\Int_($this->count);
                            break;
                        case 'root':
                            $prop->default = $this->rootExpr;
                            break;
                    }
                }
        }
    }
}
