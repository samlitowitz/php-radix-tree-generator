<?php

namespace PhpRadixTreeGenerator\DS\Trie;

final class Node
{
    /** @var array<string, Node> */
    public array $children = [];
    public ?Node $parent = null;
    public ?string $keyOnParent = null;
    public mixed $value = null;

    public function isLeaf(): bool
    {
        return empty($this->children);
    }
}
