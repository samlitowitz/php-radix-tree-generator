<?php

namespace PhpRadixTreeGenerator\Tests\Unit\DS;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PhpRadixTreeGenerator\DS\Trie;
use PhpRadixTreeGenerator\DS\Trie\Radix;

final class TrieTest extends TestCase
{
    #[DataProvider('searchNoResultsReturnsEmptyArrayProvider')]
    public function testSearchNoResultsReturnsEmptyArray(Trie $trie): void
    {
        $this->assertCount(0, $trie);
        $result = $trie->search(uniqid());
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public static function searchNoResultsReturnsEmptyArrayProvider(): array
    {
        $implementations = self::implementationsToTest();
        $testCases = [];
        foreach ($implementations as $implementation) {
            $testCases[$implementation] = [new $implementation()];
        }
        return $testCases;
    }

    /**
     * @param array<string, string> $inserts
     * @param array{key: string, matchExact: bool, values: array} $expected
     */
    #[DataProvider('searchInsertProvider')]
    public function testSearchInsert(Trie $trie, array $inserts, array $expected): void
    {
        $this->assertCount(0, $trie);
        foreach ($inserts as $key => $value) {
            $trie->insert($key, $value);
        }
        $this->assertCount(count($inserts), $trie);

        /**
         * @var string $expectedKey
         * @var bool $matchExact
         */
        foreach ($expected as ['key' => $expectedKey, 'matchExact' => $matchExact, 'values' => $expectedValues]) {
            $actualValues = $trie->search($expectedKey, $matchExact);
            $this->assertEqualsCanonicalizing($expectedValues, $actualValues);
        }
    }

    public static function searchInsertProvider(): array
    {
        $implementations = self::implementationsToTest();

        /** @var array<string, string> $inserts */
        $inserts = [
            'AA-AA' => 'AA-AA',
            'AA-AB' => 'AA-AB',
            'BB-AA' => 'BB-AA',
            'BB-AB' => 'BB-AB',
        ];
        /** @var array<string, array{0: array, 1: array}> $testCases */
        $testCases = [];
        // ensure all inserts are matched exactly
        foreach ($inserts as $k => $v) {
            $desc = sprintf(
                'exact match: %s',
                $k
            );

            $testCases[$desc] = [
                $inserts,
                [
                    [
                        'key' => $k,
                        'matchExact' => true,
                        'values' => [
                            [
                                'key' => $k,
                                'value' => $v,
                            ],
                        ],
                    ],
                ],
            ];
        }

        // test prefix matching
        $testCases['prefix match: AA'] = [
            $inserts,
            [
                [
                    'key' => 'AA',
                    'matchExact' => false,
                    'values' => [
                        [
                            'key' => 'AA-AA',
                            'value' => 'AA-AA',
                        ],
                        [
                            'key' => 'AA-AB',
                            'value' => 'AA-AB',
                        ],
                    ],
                ],
            ],
        ];
        $testCases['prefix match: AA-'] = [
            $inserts,
            [
                [
                    'key' => 'AA',
                    'matchExact' => false,
                    'values' => [
                        [
                            'key' => 'AA-AA',
                            'value' => 'AA-AA',
                        ],
                        [
                            'key' => 'AA-AB',
                            'value' => 'AA-AB',
                        ],
                    ],
                ],
            ],
        ];
        $testCases['prefix match: BB-'] = [
            $inserts,
            [
                [
                    'key' => 'BB',
                    'matchExact' => false,
                    'values' => [
                        [
                            'key' => 'BB-AA',
                            'value' => 'BB-AA',
                        ],
                        [
                            'key' => 'BB-AB',
                            'value' => 'BB-AB',
                        ],
                    ],
                ],
            ],
        ];

        $testCasesForImplementations = [];
        foreach ($implementations as $implementation) {
            foreach ($testCases as $desc => $testCase) {
                $implDesc = sprintf(
                    '%s: %s',
                    $implementation,
                    $desc
                );

                $testCasesForImplementations[$implDesc] = array_merge(
                    [
                        new $implementation(),
                    ],
                    $testCase
                );
            }
        }

        return $testCasesForImplementations;
    }

    /**
     * @param array<string, string> $inserts
     * @param array{key: string, matchExact: bool} $expected
     */
    #[DataProvider('searchDeleteProvider')]
    public function testSearchDelete(Trie $trie, array $inserts, array $expected): void
    {
        $this->assertCount(0, $trie);
        foreach ($inserts as $key => $value) {
            $trie->insert($key, $value);
        }
        $this->assertCount(count($inserts), $trie);

        /**
         * @var string $expectedKey
         * @var bool $matchExact
         */
        foreach ($expected as ['key' => $expectedKey, 'matchExact' => $matchExact]) {
            $trie->delete($expectedKey, $matchExact);
            $actualValues = $trie->search($expectedKey, $matchExact);
            $this->assertEmpty($actualValues);
            // make sure we didn't delete the entire trie or some other strange thing
            $this->assertNotEmpty($trie->search('', false));
        }
    }

    public static function searchDeleteProvider(): array
    {
        $implementations = self::implementationsToTest();

        /** @var array<string, string> $inserts */
        $inserts = [
            'AA-AA' => 'AA-AA',
            'AA-AB' => 'AA-AB',
            'BB-AA' => 'BB-AA',
            'BB-AB' => 'BB-AB',
        ];
        /** @var array<string, array{0: array, 1: array}> $testCases */
        $testCases = [];
        // ensure all inserts are matched exactly
        foreach ($inserts as $k => $v) {
            $desc = sprintf(
                'exact match: %s',
                $k
            );

            $testCases[$desc] = [
                $inserts,
                [
                    [
                        'key' => $k,
                        'matchExact' => true,
                    ],
                ],
            ];
        }

        // test prefix matching
        $testCases['prefix match: AA'] = [
            $inserts,
            [
                [
                    'key' => 'AA',
                    'matchExact' => false,
                ],
            ],
        ];
        $testCases['prefix match: AA-'] = [
            $inserts,
            [
                [
                    'key' => 'AA',
                    'matchExact' => false,
                ],
            ],
        ];
        $testCases['prefix match: BB-'] = [
            $inserts,
            [
                [
                    'key' => 'BB',
                    'matchExact' => false,
                ],
            ],
        ];

        $testCasesForImplementations = [];
        foreach ($implementations as $implementation) {
            foreach ($testCases as $desc => $testCase) {
                $implDesc = sprintf(
                    '%s: %s',
                    $implementation,
                    $desc
                );

                $testCasesForImplementations[$implDesc] = array_merge(
                    [
                        new $implementation(),
                    ],
                    $testCase
                );
            }
        }

        return $testCasesForImplementations;
    }

    public static function implementationsToTest(): array
    {
        return [
            Radix::class,
        ];
    }
}
