<?php

namespace PhpRadixTreeGenerator\RadixTree;

use Iterator;
use League\Csv\Reader;
use PhpParser\Comment;
use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\VarLikeIdentifier;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpRadixTreeGenerator\App\Console\Commands\Generate\Target;
use PhpRadixTreeGenerator\DS\Trie\Radix;
use PhpRadixTreeGenerator\IO\Writer;
use RuntimeException;

final class Generator
{
    private const GENERATED_BY = 'samlitowitz/php-radix-tree-generator';
    private const SOURCE_TEMPLATE = __DIR__ . '/../../assets/templates/radix-tree.php';

    public function __construct(
        private Target $target,
        private Iterator $dataIter,
        private Writer $w,
        private string $source
    ) {
    }

    public function generate(): void
    {
        $radix = $this->buildRadixFromIter($this->dataIter);
        $rootExpr = $this->buildArrayExpr($radix->root());
        $ast = $this->getASTFromSourceTemplate();

        $ast = $this->modifyAST($ast, $this->target, $rootExpr, $radix->count());
        $this->writePHPSource($ast);
    }

    private function buildRadixFromIter(Iterator $iter): Radix
    {
        $radix = new Radix();
        foreach ($iter as $record) {
            if (!array_key_exists($this->target->keyCol, $record)) {
                throw new RuntimeException('record does not have key column: ' . $this->target->keyCol);
            }
            $radix->insert($record[$this->target->keyCol], $record);
        }
        return $radix;
    }

    private function buildArrayExpr(\PhpRadixTreeGenerator\DS\Trie\Node $node): Array_
    {
        if ($node->isLeaf()) {
            $nodeValueExpr = null;
            switch (true) {
                case is_array($node->value):
                    $nodeValueExpr = new Array_();
                    foreach ($node->value as $cK => $cV) {
                        $nodeValueExpr->items[] = new Node\ArrayItem(
                            new Node\Scalar\String_($cV),
                            new Node\Scalar\String_($cK)
                        );
                    }
                    break;
                case is_scalar($node->value):
                    $nodeValueExpr = new Node\Scalar\String_((string)$node->value);
                    break;
                default:
                    throw new RuntimeException(sprintf('unexpected value type: %s', gettype($node->value)));
            }
            return new Array_([
                new Node\ArrayItem(
                    $nodeValueExpr,
                    new Node\Scalar\String_('value')
                ),
            ]);
        }
        $children = new Array_();
        foreach ($node->children as $key => $child) {
            $children->items[] = new Node\ArrayItem(
                $this->buildArrayExpr($child),
                new Node\Scalar\String_($key)
            );
        }
        return new Array_([
            new Node\ArrayItem(
                $children,
                new Node\Scalar\String_('children')
            ),
        ]);
    }

    /**
     * @return null|Stmt[]
     */
    private function getASTFromSourceTemplate(): ?array
    {
        $source = file_get_contents(self::SOURCE_TEMPLATE);
        if ($source === false) {
            throw new RuntimeException('failed to open source template: ' . self::SOURCE_TEMPLATE);
        }
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        return $parser->parse($source);
    }

    private function modifyAST(array $ast, Target $target, Array_ $rootExpr, int $count): array
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NodeVisitor($target, $rootExpr, $count));
        return $traverser->traverse($ast);
    }

    private function rootPropStmt(): Property
    {
        $items = [];

        return new Property(
            Modifiers::PRIVATE | Modifiers::STATIC,
            [
                new PropertyItem(
                    new VarLikeIdentifier('root'),
                    new Array_($items)
                ),
            ],
            [],
            new Identifier('array')
        );
    }

    private function searchFnStmts(): array
    {
        $_searchFnStmt = new ClassMethod(
            '_search',
            [
                'flags' => Modifiers::PRIVATE,
                'returnType' => new Identifier(new Name\FullyQualified('?array')),
                'params' => [
                    new Param(
                        new Variable('node'),
                        null,
                        new Identifier('array')
                    ),
                    new Param(
                        new Variable('prefix'),
                        null,
                        new Identifier('string')
                    ),
                ],
            ]
        );

        $searchFnStmt = new ClassMethod(
            'search',
            [
                'flags' => Modifiers::PUBLIC,
                'returnType' => new Identifier(new Name\FullyQualified('\Generator')),
                'params' => [
                    new Param(
                        new Variable('prefix'),
                        null,
                        new Identifier('string')
                    ),
                ],
                'stmts' => [
                    new Expression(
                        new Assign(
                            new Variable('found'),
                            new MethodCall(
                                new Variable('this'),
                                new Identifier('_search'),
                                [
                                    new Arg(
                                        new StaticPropertyFetch(
                                            new Name('self'),
                                            new VarLikeIdentifier('root')
                                        )
                                    ),
                                    new Arg(new Variable('prefix')),
                                ]
                            )
                        )
                    ),
                    new If_(
                        new Identical(new Variable('found'), new ConstFetch(new Name('null'))),
                        [
                            'stmts' => [
                                new Return_(new Array_([])),
                            ],
                        ]
                    ),
                    new YieldFrom(new Variable('found')),
                ],
            ]
        );

        return [
            $searchFnStmt,
            $_searchFnStmt,
        ];
    }

    private function writePHPSource(array $ast): void
    {
        $searchFnStmts = $this->searchFnStmts();
        $class = new Class_(
            $this->target->className,
            [
                'flags' => Modifiers::FINAL,
                'stmts' => array_merge(
                    $searchFnStmts,
                    [
                        $this->rootPropStmt(),
                    ]
                ),
            ]
        );
        $namespace = new Namespace_(
            new Name($this->target->namespace),
            [$class],
            [
                'comments' => [
                    new Comment(sprintf('// Code generated by %s. DO NOT EDIT.', self::GENERATED_BY)),
                    new Comment(sprintf('// source: %s', $this->source)),
                    new Comment(''),
                ],
            ]
        );

        $prettyPrinter = new Standard(['shortArraySyntax' => true]);
        $code = $prettyPrinter->prettyPrintFile($ast);
        $n = $this->w->write($code);
        if ($n !== strlen($code)) {
            throw new RuntimeException('Write failed: incomplete write');
        }
    }
}
