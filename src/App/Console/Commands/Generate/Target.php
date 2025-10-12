<?php

namespace PhpRadixTreeGenerator\App\Console\Commands\Generate;

final class Target
{
    public string $dataSource;
    public string $keyCol;
    public string $namespace;
    public string $className;
    public bool $minify = false;
}
