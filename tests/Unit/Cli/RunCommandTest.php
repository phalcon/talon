<?php

/**
 * This file is part of the Phalcon Talon.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Talon\Tests\Unit\Cli;

use Phalcon\Talon\Cli\Command\RunCommand;
use Phalcon\Talon\Cli\Input;
use Phalcon\Talon\Cli\SuiteMap;
use Phalcon\Talon\Exceptions\UnknownSuite;
use Phalcon\Talon\Tests\Fakes\Cli\RecordingProcessRunner;
use PHPUnit\Framework\TestCase;

use function dirname;
use function fopen;
use function rewind;
use function stream_get_contents;

use const PHP_BINARY;

final class RunCommandTest extends TestCase
{
    private RecordingProcessRunner $runner;

    /** @var resource */
    private $stdout;

    protected function setUp(): void
    {
        $this->runner = new RecordingProcessRunner();

        $stream = fopen('php://memory', 'w+');
        $this->assertNotFalse($stream);
        $this->stdout = $stream;
    }

    public function testAllExpandsToEveryMappedSuite(): void
    {
        $this->execute(['talon', 'run', 'all']);

        $this->assertCount(2, $this->runner->calls);
    }

    public function testBuildsTheFullCommandLine(): void
    {
        $this->execute(['talon', 'run', 'unit', '--filter', 'FooTest']);

        $call = $this->runner->calls[0];
        $this->assertSame(
            [
                PHP_BINARY,
                '-d',
                'extension=fake.so',
                $this->fixture() . '/vendor/bin/phpunit',
                '--configuration',
                $this->fixture() . '/custom/unit.xml',
                '--testdox',
                '--filter',
                'FooTest',
            ],
            $call['command']
        );
        $this->assertSame($this->fixture(), $call['cwd']);
        $this->assertSame(['GLOBAL_ENV' => 'yes', 'SHARED' => 'global'], $call['env']);
    }

    public function testMultipleSuitesAggregateWithMaxAndSummarize(): void
    {
        $this->runner->exitCodes = [1, 0];

        $exit = $this->execute(['talon', 'run', 'unit', 'db']);

        $this->assertSame(1, $exit);
        $this->assertCount(2, $this->runner->calls);
        // A blank line separates PHPUnit's output from the summary block.
        $this->assertStringStartsWith(PHP_EOL, $this->streamContents());
        $this->assertStringContainsString('unit', $this->streamContents());
        $this->assertStringContainsString('FAILED (exit 1)', $this->streamContents());
        $this->assertStringContainsString('OK', $this->streamContents());
    }

    public function testRunsTheDefaultSuiteWhenNoArguments(): void
    {
        $this->execute(['talon', 'run']);

        $this->assertCount(1, $this->runner->calls);
        $this->assertContains('--configuration', $this->runner->calls[0]['command']);
        $this->assertContains($this->fixture() . '/custom/db.xml', $this->runner->calls[0]['command']);
    }

    public function testSingleSuiteExitCodeIsForwardedVerbatim(): void
    {
        $this->runner->exitCodes = [2];

        $this->assertSame(2, $this->execute(['talon', 'run', 'unit']));
        $this->assertSame('', $this->streamContents());
    }

    public function testUnknownSuiteRunsNothing(): void
    {
        try {
            $this->execute(['talon', 'run', 'unit', 'oracle']);
            $this->fail('Expected UnknownSuite');
        } catch (UnknownSuite) {
            $this->assertSame([], $this->runner->calls);
        }
    }

    /**
     * @param list<string> $argv
     */
    private function execute(array $argv): int
    {
        $map     = new SuiteMap($this->fixture());
        $command = new RunCommand($map, $this->runner, $this->stdout);

        return $command->execute(Input::fromArgv($argv));
    }

    private function fixture(): string
    {
        return dirname(__DIR__, 2) . '/Fakes/Cli/configured';
    }

    private function streamContents(): string
    {
        rewind($this->stdout);

        return (string) stream_get_contents($this->stdout);
    }
}
