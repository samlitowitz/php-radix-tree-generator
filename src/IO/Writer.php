<?php

namespace PhpRadixTreeGenerator\IO;

interface Writer
{
    public function write(string $d): int;
}
