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

use Phalcon\Talon\Cli\SuiteMap;
use Phalcon\Talon\Exceptions\InvalidConfiguration;
use Phalcon\Talon\Exceptions\UnknownSuite;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function dirname;

final class SuiteMapTest extends TestCase
{
    public function testCastsScalarConfigValuesToStrings(): void
    {
        $map = new SuiteMap($this->fixture('casts'));

        $unit = $map->resolve('unit');
        $this->assertSame(['123'], $unit->phpFlags);
        $this->assertSame(['N' => '5'], $unit->env);

        // Integer suite keys become string suite names.
        $this->assertSame('0', $map->resolve('0')->name);
    }

    public function testClashSkipsTheDuplicateButKeepsLaterDiscoveries(): void
    {
        $map = new SuiteMap($this->fixture('clash'));

        $this->assertSame(['custom', 'zz'], array_keys($map->suites()));
    }

    public function testConfigExistenceIsReported(): void
    {
        $map = new SuiteMap($this->fixture('configured'));

        $this->assertTrue($map->resolve('unit')->configExists());
        $this->assertFalse($map->resolve('db')->configExists());
    }
    public function testConfiguredProjectMergesGlobalsIntoSuites(): void
    {
        $map = new SuiteMap($this->fixture('configured'));

        $unit = $map->resolve('unit');
        $this->assertSame($this->fixture('configured') . '/custom/unit.xml', $unit->config);
        $this->assertSame(['extension=fake.so'], $unit->phpFlags);
        $this->assertSame(['GLOBAL_ENV' => 'yes', 'SHARED' => 'global'], $unit->env);
        $this->assertSame(['--testdox'], $unit->args);

        $db = $map->resolve('db');
        $this->assertSame(['extension=fake.so', 'memory_limit=1G'], $db->phpFlags);
        $this->assertSame(
            ['GLOBAL_ENV' => 'yes', 'SHARED' => 'suite', 'DB_ONLY' => '1'],
            $db->env
        );
        $this->assertSame('db', $map->defaultSuite());
    }

    public function testConventionalProjectDiscoversSuites(): void
    {
        $map = new SuiteMap($this->fixture('conventional'));

        $this->assertSame(['unit', 'mysql'], array_keys($map->suites()));
        $this->assertSame('unit', $map->defaultSuite());
        $this->assertStringEndsWith('/phpunit.xml.dist', $map->resolve('unit')->config);
        $this->assertStringEndsWith('/resources/phpunit.mysql.xml', $map->resolve('mysql')->config);
    }

    public function testDefaultFallsBackToTheFirstSuiteWhenUnitIsAbsent(): void
    {
        $map = new SuiteMap($this->fixture('no-default'));

        $this->assertSame('alpha', $map->defaultSuite());
    }

    public function testDefaultFallsBackToUnitWhenMapped(): void
    {
        $map = new SuiteMap($this->fixture('unit-default'));

        $this->assertSame('unit', $map->defaultSuite());
    }

    public function testDefaultMustBeAMappedSuite(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage("the default suite is not defined in 'suites'");

        new SuiteMap($this->fixture('bad-default'));
    }

    public function testDogfoodsTheTalonRepoItself(): void
    {
        $map = new SuiteMap(dirname(__DIR__, 3));

        // Discovery order is glob order (alphabetical per directory).
        $this->assertSame(['mysql', 'pgsql', 'sqlite', 'unit'], array_keys($map->suites()));
        $this->assertSame('unit', $map->defaultSuite());
    }

    public function testEmptyProjectThrows(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage(
            'no talon.php and no phpunit*.xml configuration files found under '
            . $this->fixture('empty')
        );

        new SuiteMap($this->fixture('empty'));
    }

    public function testGlobalEnvMustBeAMapOfStrings(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage("'env' must be a map of strings");

        new SuiteMap($this->fixture('bad-map'));
    }

    public function testGlobalPhpMustBeAnArrayOfStrings(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage("'php' must be an array of strings");

        new SuiteMap($this->fixture('bad-php'));
    }

    public function testInvalidConfigThrows(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage('talon.php must return an array');

        new SuiteMap($this->fixture('invalid'));
    }

    public function testMissingSuitesSectionThrows(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage("talon.php must define a non-empty 'suites' array");

        new SuiteMap($this->fixture('no-suites'));
    }

    public function testPhpItemsMustBeScalar(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage("'php' must be an array of strings");

        new SuiteMap($this->fixture('bad-item'));
    }

    public function testReservedSuiteNameThrows(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage("'all' is a reserved suite name");

        new SuiteMap($this->fixture('reserved'));
    }

    public function testResolveUnknownSuiteThrows(): void
    {
        $map = new SuiteMap($this->fixture('conventional'));

        $this->expectException(UnknownSuite::class);
        $this->expectExceptionMessage("Unknown suite 'oracle'. Available suites: unit, mysql");

        $map->resolve('oracle');
    }

    public function testRootConfigWinsOverResourcesOnNameClash(): void
    {
        $map = new SuiteMap($this->fixture('clash'));

        $this->assertSame(
            $this->fixture('clash') . '/phpunit.custom.xml',
            $map->resolve('custom')->config
        );
    }

    public function testSuiteEnvItemsMustBeScalar(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage("suite 'unit' 'env' must be a map of strings");

        new SuiteMap($this->fixture('bad-env'));
    }

    public function testSuiteWithoutConfigKeyThrows(): void
    {
        $this->expectException(InvalidConfiguration::class);
        $this->expectExceptionMessage("suite 'unit' must define a 'config' path");

        new SuiteMap($this->fixture('no-config-key'));
    }

    private function fixture(string $name): string
    {
        return dirname(__DIR__, 2) . '/Fakes/Cli/' . $name;
    }
}
