<?php

namespace PhpRadixTreeGenerator\OS;

use PhpRadixTreeGenerator\IO\Closer;
use PhpRadixTreeGenerator\IO\Writer;
use RuntimeException;

final class File implements Closer, Writer
{
    /** @var resource $h */
    private $h;
    /** @var string */
    private $name;

    private function __construct(string $name)
    {
        $this->setName($name);
    }

    /**
     * @param resource|null $context
     */
    public static function openFile(
        string $filename,
        string $mode,
        bool $use_include_path = false,
        $context = null
    ): File {
        $f = new File($filename);
        $h = fopen($filename, $mode, $use_include_path, $context);
        if ($h === false) {
            throw new RuntimeException(
                sprintf(
                    'Failed to open %s with mode %s',
                    $filename,
                    $mode
                )
            );
        }
        $f->setHandle($h);
        return $f;
    }

    public function close(): void
    {
        if ($this->h === null) {
            return;
        }
        \fclose($this->h);
        $this->h = null;
    }

    public function write(string $d): int
    {
        if ($this->h === null) {
            throw new RuntimeException('Write failed: invalid resource');
        }
        $n = fwrite($this->h, $d);
        if ($n === false) {
            throw new RuntimeException('Write failed: unknown cause');
        }
        if ($n !== strlen($d)) {
            throw new RuntimeException('Write failed: incomplete write');
        }
        return $n;
    }

    /**
     * @param resource $h
     */
    private function setHandle($h): void
    {
        $this->h = $h;
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function setName(string $name): void
    {
        $this->name = $name;
    }
}
