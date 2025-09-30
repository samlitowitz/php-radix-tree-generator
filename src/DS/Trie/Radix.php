<?php

namespace PhpRadixTreeGenerator\DS\Trie;

use OutOfBoundsException;
use PhpRadixTreeGenerator\DS\Trie;

final class Radix implements Trie
{
    private Node $root;
    /** @var int<0, max> */
    private int $count = 0;

    public function __construct()
    {
        $this->root = new Node();
    }

    public function root(): Node
    {
        return $this->root;
    }

    public function search(string $key, bool $matchExact = true): array
    {
        return $this->searchInternal($this->root, $key, '', $matchExact);
    }

    public function insert(string $key, mixed $value): void
    {
        if (empty($key)) {
            throw new OutOfBoundsException('key must not be empty');
        }
        $node = $this->insertInternal($this->root, $key);
        $node->value = $value;
        $this->count++;
    }

    public function delete(string $key, bool $matchExact = true): void
    {
        $this->deleteInternal($this->root, $key, '', $matchExact);
    }

    public function count(): int
    {
        return $this->count;
    }

    /** @return array<array{key: string, value: mixed}> */
    private function searchInternal(
        Node $node,
        string $remainingKeyToFind,
        string $keyToNode,
        bool $matchExact = true
    ): array {
        // leaf node and no remaining key to find, we're at our destination
        if ($node->isLeaf() && empty($remainingKeyToFind)) {
            return [
                [
                    'key' => $keyToNode,
                    'value' => $node->value,
                ],
            ];
        }
        // no remaining key to find, get all children
        if (!$matchExact && empty($remainingKeyToFind)) {
            return $this->getAllChildren($node, $keyToNode);
        }

        foreach ($node->children as $childKey => $child) {
            // first char mismatch, cant match
            if ($remainingKeyToFind[0] !== $childKey[0]) {
                continue;
            }
            $lenDiff = strlen($childKey) - strlen($remainingKeyToFind);
            switch (true) {
                // remaining key is the same length as the child key
                case $lenDiff === 0:
                    $isExactMatch = $remainingKeyToFind === $childKey;
                    // match exactly and is exact match, get child node data and end
                    if ($matchExact && $isExactMatch) {
                        return $this->searchInternal($child, '', $keyToNode . $remainingKeyToFind, $matchExact);
                    }
                    // prefix matching and exact match, get all children and end
                    if ($isExactMatch) {
                        return $this->getAllChildren($child, $keyToNode . $remainingKeyToFind);
                    }
                    // same length but not exact match, no matches found
                    return [];

                // remaining key is longer than child key, can match exact
                case $lenDiff < 0:
                    return $this->searchInternal(
                        $child,
                        substr($remainingKeyToFind, strlen($childKey)),
                        $keyToNode . $childKey,
                        $matchExact
                    );

                // remaining key is shorter than child key, can't match exact
                // $lenDiff > 0 is always true
                case !$matchExact:
                    return $this->getAllChildren($child, $keyToNode . $childKey);
            }
        }
        return [];
    }

    /** @return array<array{key: string, value: mixed}> */
    private function getAllChildren(Node $node, string $keyToNode): array
    {
        if ($node->isLeaf()) {
            return [
                [
                    'key' => $keyToNode,
                    'value' => $node->value,
                ],
            ];
        }
        $found = [];
        foreach ($node->children as $childKey => $child) {
            $found = array_merge(
                $found,
                $this->getAllChildren($child, $keyToNode . $childKey)
            );
        }
        return $found;
    }

    private function deleteInternal(
        Node $node,
        string $remainingKeyToFind,
        string $keyToNode,
        bool $matchExact = true
    ): void {
        // leaf node and no remaining key to find, no-op
        if ($node->isLeaf() && empty($remainingKeyToFind)) {
            return;
        }
        // no remaining key to find, delete all children
        if (!$matchExact && empty($remainingKeyToFind)) {
            $node->children = [];
        }

        foreach ($node->children as $childKey => $child) {
            // first char mismatch, cant match
            if ($remainingKeyToFind[0] !== $childKey[0]) {
                continue;
            }
            $lenDiff = strlen($childKey) - strlen($remainingKeyToFind);
            switch (true) {
                // remaining key is the same length as the child key
                case $lenDiff === 0:
                    $isExactMatch = $remainingKeyToFind === $childKey;
                    // match exactly and is exact match, delete leaf
                    if ($matchExact && $isExactMatch) {
                        // not a leaf node, we're done
                        if (!$child->isLeaf()) {
                            return;
                        }
                        // delete exactly matched leaf
                        unset($node->children[$childKey]);
                        // node has more than one remaining child, no need to re-index
                        if (count($node->children) > 1) {
                            return;
                        }
                        // no parent, can't re-index
                        if ($node->parent === null) {
                            throw new \RuntimeException('parent not defined');
                        }
                        // no key on parent, can't re-index
                        if ($node->keyOnParent === null) {
                            throw new \RuntimeException('key on parent not defined');
                        }
                        if (!array_key_exists($node->keyOnParent, $node->parent->children)) {
                            throw new \RuntimeException('key on parent not mapped to child on parent');
                        }
                        // node has exactly one remaining child, merge child and update index on parent
                        $remainingChildKey = array_keys($node->children);
                        if (count($remainingChildKey) !== 1) {
                            throw new \RuntimeException('expected exactly one child remaining, more than one child remains');
                        }
                        $remainingChildKey = $remainingChildKey[0];
                        $remainingChild = $node->children[$remainingChildKey];
                        // remove node's current key on parent
                        unset($node->parent->children[$node->keyOnParent]);
                        // set new key on parent for node
                        $newKey = $node->keyOnParent . $remainingChildKey;
                        $node->parent->children[$newKey] = $node;
                        $node->keyOnParent = $newKey;
                        // remove all children on node and inherit child's value
                        $node->children = [];
                        $node->value = $remainingChild->value;
                        return;
                    }
                    // prefix matching and exact match, delete all children and end
                    if ($isExactMatch) {
                        $node->children = [];
                    }
                    // same length but not exact match, no matches found
                    return;

                // remaining key is longer than child key, can match exact
                case $lenDiff < 0:
                    $this->deleteInternal(
                        $child,
                        substr($remainingKeyToFind, strlen($childKey)),
                        $keyToNode . $childKey,
                        $matchExact
                    );
                    return;

                // remaining key is shorter than child key, can't match exact
                // $lenDiff > 0 is always true
                case !$matchExact:
                    unset($node->children[$childKey]);
                    return;
                default:
                    throw new \RuntimeException('Unexpected value');
            }
        }
    }

    private function insertInternal(Node $node, string $key): Node
    {
        // first child, matches key
        if (empty($node->children)) {
            $newChild = new Node();
            $newChild->parent = $node;
            $newChild->keyOnParent = $key;
            $node->children[$key] = $newChild;
            return $newChild;
        }
        $keyLen = strlen($key);
        $keyMax = $keyLen - 1;
        $keyPrefix = '';
        for ($i = 0; $i < $keyLen; $i++) {
            $keyPrefix .= $key[$i];
            if (!isset($node->children[$keyPrefix])) {
                continue;
            }
            $child = $node->children[$keyPrefix];
            // complete key matched, existing node found
            if ($i === $keyMax) {
                return $child;
            }
            // partial key match, search child node unmatched portion of key
            return $this->insertInternal($child, substr($key, $i));
        }
        // check for shared key prefixes and split if found
        $keyToSplit = null;
        $splitKey = null;
        foreach ($node->children as $childKey => $child) {
            $childKey = (string) $childKey;
            $sharedKey = '';
            for ($i = 0; $i < $keyLen; $i++) {
                if ($childKey[$i] !== $key[$i]) {
                    break;
                }
                $sharedKey .= $key[$i];
            }
            // found shared key prefixes, split on this
            if (!empty($sharedKey)) {
                $keyToSplit = $childKey;
                $splitKey = $sharedKey;
                break;
            }
        }

        $doSplit = $keyToSplit !== null && $splitKey !== null;
        // no keys to split, insert new child for key
        if (!$doSplit) {
            $newChild = new Node();
            $newChild->parent = $node;
            $newChild->keyOnParent = $key;
            $node->children[$key] = $newChild;
            return $newChild;
        }
        // found key to split
        $splitKeyNewKey = substr($keyToSplit, strlen($splitKey));
        $newKeyNewKey = substr($key, strlen($splitKey));
        // create new child for split key
        $splitNode = new Node();
        $splitNode->parent = $node;
        $splitNode->keyOnParent = $splitKey;
        $node->children[$splitKey] = $splitNode;
        // add key to split to split node
        $splitNode->children[$splitKeyNewKey] = $node->children[$keyToSplit];
        $splitNode->children[$splitKeyNewKey]->parent = $splitNode;
        $splitNode->children[$splitKeyNewKey]->keyOnParent = $splitKeyNewKey;
        unset($node->children[$keyToSplit]);
        // add new child for inserted key
        $newChild = new Node();
        $newChild->parent = $splitNode;
        $newChild->keyOnParent = $newKeyNewKey;
        $splitNode->children[$newKeyNewKey] = $newChild;
        return $newChild;
    }
}
