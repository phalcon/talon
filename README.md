# Phalcon Talon

[![Latest Version][packagist-version-badge]][packagist-version-link]
[![PHP Version][php-version-badge]][packagist-version-link]
[![Total Downloads][packagist-downloads-badge]][packagist-downloads-link]
[![License][license-badge]][license-link]

[![Talon CI][talon-ci-badge]][talon-ci-link]
[![Quality Gate Status][sonar-quality-badge]][sonar-link]
[![Coverage][sonar-coverage-badge]][sonar-link]
[![PDS Skeleton][pds-skeleton-badge]][pds-skeleton-link]

[![Discord][discord-badge]][discord-link]
[![Contributors][contributors-badge]][contributors-link]
[![OpenCollective Backers][oc-backers-badge]][oc-backers-link]
[![OpenCollective Sponsors][oc-sponsors-badge]][oc-sponsors-link]

Test harness and Phalcon bootstrapping for PHPUnit and beyond - the part of Phalcon that
catches the bugs.

Talon provides framework-neutral **traits** (the core), ready-to-extend **PHPUnit base
classes**, and a one-liner **bootstrap** so any Phalcon project can write unit, integration,
and functional tests with minimal boilerplate.

## Requirements

- PHP `^8.1`
- Phalcon - either the `ext-phalcon` C extension (`^5`) **or** the `phalcon/phalcon` PHP
  implementation (`^6`). Talon detects whichever is present.

## Install

```bash
composer require --dev phalcon/talon
```

## Bootstrap (one-liner)

```php
// tests/bootstrap.php
require __DIR__ . '/../vendor/autoload.php';

use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;

Talon::boot(Settings::fromEnv());
```

Need setup hooks (the old `loadIni` / `loadFolders`)? Use the bootstrap runner:

```php
use Phalcon\Talon\Bootstrap\Runner;
use Phalcon\Talon\Bootstrap\Stage;
use Phalcon\Talon\Settings;

Runner::for(Settings::fromArray(['root' => __DIR__ . '/..']))
    ->before(Stage::Environment, fn () => ini_set('memory_limit', '512M'))
    ->after(Stage::Directories, fn ($settings) => mkdir($settings->path('tests/_output/screens'), 0777, true))
    ->boot();
```

## Unit tests

```php
use Phalcon\Talon\PHPUnit\AbstractUnitTestCase;

final class CalculatorTest extends AbstractUnitTestCase
{
    public function testInternal(): void
    {
        $this->assertSame(5, $this->callProtectedMethod(new Calculator(), 'add', 2, 3));
    }
}
```

`AbstractUnitTestCase` gives you `callProtectedMethod()`, `getProtectedProperty()`,
`setProtectedProperty()`, `invokeMethod()`, `getNewFileName()`, `safeDeleteFile()`,
`safeDeleteDirectory()`, `assertFileContentsContains()`, `checkExtensionIsLoaded()`, and
`checkPhalconAvailable()`.

## Database tests

```php
use Phalcon\Talon\PHPUnit\AbstractDatabaseTestCase;

final class UserTest extends AbstractDatabaseTestCase
{
    public function testSeeded(): void
    {
        $this->assertInDatabase('users', ['email' => 'nikos@niden.net']);
    }
}
```

The driver comes from the `driver` env (`sqlite`, `mysql`, `pgsql`); credentials come from
`Settings` (env vars by default - see `resources/.env.example`).

## Functional tests

The package never owns your container - hand it your configured application:

```php
use Phalcon\Talon\PHPUnit\AbstractFunctionalTestCase;

final class HomeTest extends AbstractFunctionalTestCase
{
    protected function appFactory(): callable
    {
        return fn () => require __DIR__ . '/../app/bootstrap.php'; // returns a configured Application/Micro
    }

    public function testHome(): void
    {
        $this->dispatch('/');
        $this->assertController('index');
        $this->assertResponseContentContains('Welcome');
    }
}
```

## Service tests (Redis / Memcached)

```php
use Phalcon\Talon\PHPUnit\AbstractServicesTestCase;

final class CacheTest extends AbstractServicesTestCase
{
    public function testRedis(): void
    {
        $this->setRedisKey('key', 'value');
        $this->assertSame('value', $this->getRedisKey('key'));
    }
}
```

Service tests skip automatically when the backend is unreachable.

## Mocking a Resultset (no database)

```php
use Phalcon\Talon\Traits\ResultSetTrait;

final class ReportTest extends \PHPUnit\Framework\TestCase
{
    use ResultSetTrait;

    public function testReport(): void
    {
        $resultset = $this->mockResultSet([$modelA, $modelB]);
        $this->assertCount(2, $resultset);
    }
}
```

## Custom configuration

Override `getSettings()` in a project base class, or pass `Settings::fromArray([...])` to
`Talon::boot()`:

```php
Talon::boot(Settings::fromArray([
    'root' => dirname(__DIR__),
    'db'   => [
        'mysql'  => ['host' => '127.0.0.1', 'port' => 3306, 'dbname' => 'app', 'username' => 'root', 'password' => ''],
        'sqlite' => ['dbname' => ':memory:'],
    ],
]));
```

## Beyond PHPUnit

The traits are the core public API and carry no PHPUnit base-class requirement for their
non-assertion helpers, so Pest (`uses(...)`) and other runners can consume them too. Pest and
Codeception adapters are planned for a future release.

## License

BSD-3-Clause. See [LICENSE](LICENSE).

<!-- Badges -->
[packagist-version-badge]:   https://img.shields.io/packagist/v/phalcon/talon?include_prereleases&style=flat-square&logo=packagist&logoColor=white
[packagist-version-link]:    https://packagist.org/packages/phalcon/talon
[packagist-downloads-badge]: https://img.shields.io/packagist/dt/phalcon/talon?style=flat-square&logo=packagist&logoColor=white
[packagist-downloads-link]:  https://packagist.org/packages/phalcon/talon/stats
[php-version-badge]:          https://img.shields.io/packagist/php-v/phalcon/talon?style=flat-square&logo=php&logoColor=white
[license-badge]:             https://img.shields.io/github/license/phalcon/talon?style=flat-square&logo=opensourceinitiative&logoColor=white
[license-link]:              https://github.com/phalcon/talon/blob/master/LICENSE
[talon-ci-badge]:            https://github.com/phalcon/talon/actions/workflows/main.yml/badge.svg?branch=master
[talon-ci-link]:             https://github.com/phalcon/talon/actions/workflows/main.yml
[sonar-quality-badge]:       https://sonarcloud.io/api/project_badges/measure?project=phalcon_talon&metric=alert_status
[sonar-coverage-badge]:      https://sonarcloud.io/api/project_badges/measure?project=phalcon_talon&metric=coverage
[sonar-link]:                https://sonarcloud.io/summary/new_code?id=phalcon_talon
[pds-skeleton-badge]:        https://img.shields.io/badge/pds-skeleton-blue.svg?style=flat-square
[pds-skeleton-link]:         https://github.com/php-pds/skeleton
[discord-badge]:             https://img.shields.io/discord/310910488152375297?label=Discord&logo=discord&style=flat-square
[discord-link]:              https://phalcon.io/discord
[contributors-badge]:        https://img.shields.io/github/contributors/phalcon/talon?style=flat-square&logo=github&logoColor=white
[contributors-link]:         https://github.com/phalcon/talon/graphs/contributors
[oc-backers-badge]:          https://img.shields.io/opencollective/backers/phalcon?style=flat-square&logo=opencollective&logoColor=white
[oc-backers-link]:           https://opencollective.com/phalcon
[oc-sponsors-badge]:         https://img.shields.io/opencollective/sponsors/phalcon?style=flat-square&logo=opencollective&logoColor=white
[oc-sponsors-link]:          https://opencollective.com/phalcon
