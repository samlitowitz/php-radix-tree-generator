<?php

declare(strict_types=1);

namespace PhpRadixTreeGenerator\App\Console\Commands;

use InvalidArgumentException;
use League\Csv\Reader;
use PhpRadixTreeGenerator\App\Console\Commands\Generate\Target;
use PhpRadixTreeGenerator\OS\File;
use PhpRadixTreeGenerator\RadixTree\Generator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\JsonStreamer\JsonStreamReader;
use Symfony\Component\TypeInfo\Type;

#[AsCommand(
    name: 'generate',
    description: 'Generate PHP Radix Tree implementations for a set of data'
)]
final class Generate extends Command
{
    private const TARGETS_ARG = 'targets';
    private const OUTPUT_DIR_ARG = 'output-dir';

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::TARGETS_ARG,
                InputArgument::REQUIRED,
                'Targets file'
            )
            ->addArgument(
                self::OUTPUT_DIR_ARG,
                InputArgument::REQUIRED,
                'Output directory'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $outputDirectory */
        $outputDirectory = $input->getArgument(self::OUTPUT_DIR_ARG);
        /** @var string $targetArg */
        $targetArg = $input->getArgument(self::TARGETS_ARG);
        $rawTargets = file_get_contents($targetArg);
        if ($rawTargets === false) {
            throw new InvalidArgumentException(
                sprintf(
                    'unable to load targets: %s',
                    $targetArg
                )
            );
        }

        $r = JsonStreamReader::create();
        /** @var Target[] $targets */
        $targets = $r->read($rawTargets, Type::iterable(Type::object(Target::class)));
        foreach ($targets as $target) {
            $w = File::openFile($outputDirectory . '/' . $target->className . '.php', 'w+');
            try {
                // load and parse data
                $r = Reader::createFromPath($target->dataSource);
                $r->setHeaderOffset(0);
                $r->setEscape('');
                $g = new Generator($target, $r->getRecords(), $w, $targetArg);
                $g->generate();
            } finally {
                $w->close();
            }
        }
        return Command::SUCCESS;
    }
}
