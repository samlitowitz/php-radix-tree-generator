<?php

namespace PhpRadixTreeGenerator\Tests\GeneratedCode;

use PhpRadixTreeGenerator\App\Console\Commands\Generate\Target;
use PhpRadixTreeGenerator\OS\File;
use PhpRadixTreeGenerator\RadixTree\Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RadixTreeTest extends TestCase
{
    private const CLASS_NAME = 'RadixTreeUnderTest';
    /** @var array<string, array<string, string>> */
    private const DATA = [
        ['col-1' => 'AA-AA-1', 'col-2' => 'AA-AA-2', 'col-3' => 'AA-AA-3'],
        ['col-1' => 'AA-AB-1', 'col-2' => 'AA-AB-2', 'col-3' => 'AA-AB-3'],
        ['col-1' => 'BB-AA-1', 'col-2' => 'BB-AA-2', 'col-3' => 'BB-AA-3'],
        ['col-1' => 'BB-AB-1', 'col-2' => 'BB-AB-2', 'col-3' => 'BB-AB-3'],
    ];
    private const KEY_TO_INDEX_ON = 'col-1';
    /** @var array<string> */
    private array $filesToDelete = [];

    public function setUp(): void
    {
        $this->generateRadixTreeUnderTest(
            self::CLASS_NAME,
            self::KEY_TO_INDEX_ON,
            new \ArrayIterator(self::DATA)
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();
        foreach ($this->filesToDelete as $fileToDelete) {
            unlink($fileToDelete);
        }
        $this->filesToDelete = [];
    }

    #[DataProvider('searchProvider')]
    public function testSearch(array $inserts, string $keyToSearch, bool $matchExact, array $expectedData): void
    {
        $expectedDataByKey = [];
        foreach ($expectedData as $expected) {
            $expectedDataByKey[$expected[self::KEY_TO_INDEX_ON]] = [
                'key' => $expected[self::KEY_TO_INDEX_ON],
                'value' => $expected,
            ];
        }

        $radix = new RadixTreeUnderTest();

        $actuals = $radix->search($keyToSearch, $matchExact);
        $this->assertSame(count($expectedDataByKey), count($actuals));
        foreach ($actuals as ['key' => $actualKey, 'value' => $actualValue]) {
            $this->assertSame($actualValue[self::KEY_TO_INDEX_ON], $actualKey);
            $this->assertArrayHasKey($actualKey, $expectedDataByKey);
            [
                'value' => $expectedValue,
            ] = $expectedDataByKey[$actualKey];
            foreach ($actualValue as $k => $v) {
                $this->assertArrayHasKey($k, $expectedValue);
                $this->assertSame($expectedValue[$k], $v);
                unset($expectedValue[$k]);
            }
            $this->assertEmpty($expectedValue);
        }
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string, 2: bool, 3: array<string, string>}>
     */
    public static function searchProvider(): array
    {
        $inserts = self::DATA;
        /** @var array<string, array{0: array<string, string>, 1: string, 2: bool, 3: array<string, string>}> $testCases */
        $testCases = [];
        // ensure all inserts are matched exactly
        foreach ($inserts as $v) {
            $key = $v[self::KEY_TO_INDEX_ON];

            // exact match
            $desc = sprintf(
                'exact match: %s',
                $key
            );
            $testCases[$desc] = [
                $inserts,
                $key,
                true,
                array_filter(
                    self::DATA,
                    function (array $data) use ($key): bool {
                        return $data[self::KEY_TO_INDEX_ON] === $key;
                    }
                ),
            ];

            // prefix matching
            $prefixKey = substr($key, 0, random_int(2, strlen($key) - 2));
            $desc = sprintf(
                'prefix match: %s',
                $prefixKey
            );
            $testCases[$desc] = [
                $inserts,
                $prefixKey,
                false,
                array_filter(
                    self::DATA,
                    function (array $data) use ($prefixKey): bool {
                        return str_starts_with($data[self::KEY_TO_INDEX_ON], $prefixKey);
                    }
                ),
            ];
        }
        return $testCases;
    }

    private function generateRadixTreeUnderTest(string $className, string $keyToIndexOn, \Iterator $dataIter): void
    {
        $fileToGenerate = __DIR__ . '/' . self::CLASS_NAME . '.php';
        $w = File::openFile($fileToGenerate, 'w+');
        try {
            $target = new Target();

            $target->dataSource = ':in-memory:';
            $target->keyCol = $keyToIndexOn;
            $target->namespace = 'PhpRadixTreeGenerator\Tests\GeneratedCode';
            $target->className = $className;
            $g = new Generator($target, $dataIter, $w, self::class);
            $g->generate();

            require_once $fileToGenerate;
            $w->close();
        } finally {
            $w->close();
        }
    }
}
