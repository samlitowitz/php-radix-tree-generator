<?php

namespace PhpRadixTreeGenerator\Tests\GeneratedCode;

final class RadixTreeUnderTest implements \Countable
{
    public function search(string $key, bool $matchExact = true): array
    {
        return $this->searchInternal(self::$root, $key, '', $matchExact);
    }
    public function count(): int
    {
        return self::$count;
    }
    /** @return array<array{key: string, value: mixed}> */
    private function searchInternal(array $node, string $remainingKeyToFind, string $keyToNode, bool $matchExact = true): array
    {
        // leaf node and no remaining key to find, we're at our destination
        if (!isset($node['children']) && empty($remainingKeyToFind)) {
            return [['key' => $keyToNode, 'value' => $node['value']]];
        }
        // no remaining key to find, get all children
        if (!$matchExact && empty($remainingKeyToFind)) {
            return $this->getAllChildren($node, $keyToNode);
        }
        foreach ($node['children'] as $childKey => $child) {
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
                    return $this->searchInternal($child, substr($remainingKeyToFind, strlen($childKey)), $keyToNode . $childKey, $matchExact);
                // remaining key is shorter than child key, can't match exact
                // $lenDiff > 0 is always true
                case !$matchExact:
                    return $this->getAllChildren($child, $keyToNode . $childKey);
            }
        }
        return [];
    }
    /** @return array<array{key: string, value: mixed}> */
    private function getAllChildren(array $node, string $keyToNode): array
    {
        if (!isset($node['children'])) {
            return [['key' => $keyToNode, 'value' => $node['value']]];
        }
        $found = [];
        foreach ($node['children'] as $childKey => $child) {
            $found = array_merge($found, $this->getAllChildren($child, $keyToNode . $childKey));
        }
        return $found;
    }
    /** @var array<string, array{children: array, value: ?string}> $root */
    private static array $root = ['children' => ['AA-A' => ['children' => ['A-1' => ['value' => ['col-1' => 'AA-AA-1', 'col-2' => 'AA-AA-2', 'col-3' => 'AA-AA-3']], 'B-1' => ['value' => ['col-1' => 'AA-AB-1', 'col-2' => 'AA-AB-2', 'col-3' => 'AA-AB-3']]]], 'BB-A' => ['children' => ['A-1' => ['value' => ['col-1' => 'BB-AA-1', 'col-2' => 'BB-AA-2', 'col-3' => 'BB-AA-3']], 'B-1' => ['value' => ['col-1' => 'BB-AB-1', 'col-2' => 'BB-AB-2', 'col-3' => 'BB-AB-3']]]]]];
    /** @var int<0, max> */
    private static int $count = 4;
}