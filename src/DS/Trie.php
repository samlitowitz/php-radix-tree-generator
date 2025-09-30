<?php

namespace PhpRadixTreeGenerator\DS;

interface Trie extends \Countable
{
    /**
     * @return array<array{key: string, value: mixed}>
     */
    public function search(string $key, bool $matchExact = true): array;

    public function insert(string $key, mixed $value): void;

    public function delete(string $key, bool $matchExact = true): void;

    public function count(): int;
}
