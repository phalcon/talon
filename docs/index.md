# Phalcon Talon

Test harness and Phalcon bootstrapping for PHPUnit and beyond - the part of Phalcon that
catches the bugs.

Talon gives any Phalcon project a small, composable foundation for writing unit,
integration, and functional tests:

- **Engine classes** - framework-neutral logic (`Settings`, `Environment`,
  `Database\Connection`, `Bootstrap\Runner`, `Bootstrap\DiFactory`, the `Talon` facade).
- **Traits** - the core public API; usable from PHPUnit, Pest, or anything with a
  PHPUnit-compatible `$this`.
- **PHPUnit base classes** - thin, ready-to-extend `Abstract*TestCase` classes that compose
  the traits.

---

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Architecture](#architecture)
- [Bootstrapping](#bootstrapping)
- [Configuration](#configuration)
- [Directory accessors](#directory-accessors)
- [Runtime detection](#runtime-detection)
- [Base test cases](#base-test-cases)
  - [Unit](#unit-tests)
  - [Database](#database-tests)
  - [Services](#service-tests)
  - [Functional](#functional-tests)
  - [Browser](#browser-tests)
- [Schema fixtures](#schema-fixtures)
- [Mocking a resultset](#mocking-a-resultset)
- [Using the traits directly](#using-the-traits-directly)
- [Exceptions](#exceptions)
- [Engine reference](#engine-reference)
- [Running the test suite](#running-the-test-suite)
- [Environment variables](#environment-variables)

---

## Requirements

- PHP `^8.1`
- Phalcon - **either** the `ext-phalcon` C extension (`^5`) **or** the `phalcon/phalcon`
  PHP implementation (`^6`). Talon detects whichever is present; you do not configure this.

`composer.json` deliberately does **not** hard-require either provider (Composer cannot
express "one of an extension or a package"). The provider is enforced at runtime; see
[runtime detection](#runtime-detection).

## Installation

```bash
composer require --dev phalcon/talon
```

---

## Architecture

Dependencies point downward only:

```
PHPUnit Abstract* bases   →  compose traits          (convenience, optional)
        ↓
Traits                    →  glue engine onto $this   (the core public API)
        ↓
Engine classes            →  framework-neutral logic  (no PHPUnit dependency)
```

The heavy lifting lives in plain engine classes so it can be reused outside a `TestCase`
(a Codeception module, a Behat context, a CLI seeder). Traits expose that engine on
`$this`; the `Abstract*TestCase` classes simply compose the relevant traits.

Traits split into two kinds:

- **Pure** - `ReflectionTrait` and the file-operation half of `FileSystemTrait`. No PHPUnit
  dependency.
- **Host-needed** - `DatabaseTrait`, `ServicesTrait`, `ResultSetTrait`, `FunctionalTrait`,
  `BrowserTrait`, the `*AssertionsTrait` pair, and the `assertFileContents*` helpers. They call `$this->assert*()` /
  `$this->getMockBuilder()`, so they require a PHPUnit-compatible host.

---

## Bootstrapping

Call `Talon::boot()` once from your PHPUnit bootstrap file:

```php
// tests/bootstrap.php
require __DIR__ . '/../vendor/autoload.php';

use Phalcon\Talon\Settings;
use Phalcon\Talon\Talon;

Talon::boot(Settings::fromEnv());
```

`Talon::boot()` runs three ordered stages and stores the resolved `Settings` in a single
static slot that the traits read by default:

| Stage | Default behavior |
|-------|------------------|
| `Environment` | `error_reporting(E_ALL)`, UTC timezone |
| `Directories` | creates the output directory if missing |
| `Settings` | registers the `Settings` instance on the `Talon` facade |

### Lifecycle hooks

Need the old `loadIni` / `loadFolders` style of setup? Use the `Runner` directly and attach
`before` / `after` hooks per stage - no subclassing required:

```php
use Phalcon\Talon\Bootstrap\Runner;
use Phalcon\Talon\Bootstrap\Stage;
use Phalcon\Talon\Settings;

Runner::for(Settings::fromArray(['root' => __DIR__ . '/..']))
    ->before(Stage::Environment, fn () => ini_set('memory_limit', '512M'))
    ->after(Stage::Directories, fn ($settings) => mkdir($settings->outputPath('screens'), 0777, true))
    ->boot();
```

For deeper control, subclass `Runner` and override `initEnvironment()`,
`initDirectories()`, or `initSettings()`.

### The `Talon` facade

```php
Talon::boot(?Contracts\Settings $settings = null): Contracts\Settings  // bootstrap; defaults to Settings::fromEnv()
Talon::useSettings(Contracts\Settings $settings): void                 // register the active settings
Talon::settings(): Contracts\Settings                                  // the active settings (lazy fromEnv())
Talon::reset(): void                                                   // clear the slot (test isolation)
```

---

## Configuration

`Settings` is the single source of configuration. Build it explicitly or from the
environment:

```php
use Phalcon\Talon\Settings;

// Explicit
$settings = Settings::fromArray([
    'root' => dirname(__DIR__),
    'db'   => [
        'mariadb' => ['host' => '127.0.0.1', 'port' => 3307, 'dbname' => 'app',
                      'username' => 'root', 'password' => '', 'charset' => 'utf8mb4'],
        'mysql'   => ['host' => '127.0.0.1', 'port' => 3306, 'dbname' => 'app',
                      'username' => 'root', 'password' => '', 'charset' => 'utf8mb4'],
        'sqlite'  => ['dbname' => ':memory:'],
    ],
    'services' => [
        'redis'        => ['host' => '127.0.0.1', 'port' => 6379, 'index' => 0],
        'memcached'    => ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 0],
        'redisCluster' => ['hosts' => ['10.0.0.1:6379', '10.0.0.2:6379'], 'auth' => ''],
        'beanstalk'    => ['host' => '127.0.0.1', 'port' => 11300],
    ],
    'paths' => ['output' => 'build/out'],   // optional directory overrides
]);

// From environment variables (with sane defaults)
$settings = Settings::fromEnv();
```

`fromArray()` requires a non-empty `root`. `fromEnv()` reads the `DATA_*` variables (see
[environment variables](#environment-variables)) and auto-discovers `root` (see
[directory accessors](#directory-accessors)).

### The `getSettings()` seam

Every host-needed trait resolves configuration through `getSettings()`, which by default
returns `Talon::settings()`. To customize for a subset of tests, override it in a project
base class:

```php
abstract class TestCase extends \Phalcon\Talon\PHPUnit\AbstractDatabaseTestCase
{
    protected function getSettings(): \Phalcon\Talon\Contracts\Settings
    {
        return \Phalcon\Talon\Settings::fromArray([/* ... */]);
    }
}
```

### Database options & DSN

```php
$settings->getDatabaseDsn('mariadb');   // mysql:host=...;port=...;dbname=...;charset=...
$settings->getDatabaseDsn('mysql');     // mysql:host=...;port=...;dbname=...;charset=...
$settings->getDatabaseDsn('pgsql');     // pgsql:host=...;port=...;dbname=...
$settings->getDatabaseDsn('sqlite');    // sqlite:...
$settings->getDatabaseOptions('mysql'); // ['host'=>..., 'username'=>..., ...]
```

Supported drivers: `mariadb`, `mysql`, `pgsql`, `sqlite`. Any other driver throws
`Exceptions\UnknownDriver`.

`mariadb` and `mysql` are configured independently - separate `DATA_MARIADB_*` and
`DATA_MYSQL_*` variables, so they can point at different servers - but they share the
`mysql:` DSN prefix because MariaDB connects through `pdo_mysql`. PDO therefore reports
`mysql` as the driver name, which is correct: the SQL dialect is the same. Use
`getDriver()` when a test needs to tell the two servers apart, and `getDialect()` when it
needs the SQL flavor.

### Service options

Every other configured service (`redis`, `memcached`, `redisCluster`, `beanstalk`, and any
service you add under the `services` key) is read through one generic accessor:

```php
$settings->getServiceOptions('redis');        // ['host'=>..., 'port'=>..., 'index'=>...]
$settings->getServiceOptions('memcached');    // ['host'=>..., 'port'=>..., 'weight'=>...]
$settings->getServiceOptions('redisCluster'); // ['hosts'=>[...], 'auth'=>...]
$settings->getServiceOptions('beanstalk');    // ['host'=>..., 'port'=>...]
$settings->getServiceOptions('unknown');      // [] - unknown names return empty, never throw
```

Unlike `getDatabaseOptions()`, an unrecognized name doesn't throw - it returns `[]`. This
keeps `getServiceOptions()` a plain, permissive lookup, so adding a new service to the
`services` config section never requires a new method on `Settings`.

---

## Directory accessors

`Settings` provides named, root-relative path helpers (the OO equivalent of Codeception's
`codecept_*_dir()`):

```php
$settings->rootPath();              // project root
$settings->rootPath('composer.json');
$settings->testsPath();             // {root}/tests
$settings->dataPath();              // {root}/tests/_data
$settings->outputPath();            // {root}/tests/_output
$settings->cachePath();             // {root}/tests/_output/cache
$settings->logsPath();              // {root}/tests/_output/logs
$settings->supportPath();           // {root}/tests/support
```

Each accepts an optional relative suffix and can be overridden through the `paths` config
key (e.g. `'paths' => ['output' => 'build/out']`).

**Root discovery.** `Settings::fromEnv()` anchors `root` by walking up from the current
working directory to the nearest `composer.json` (falling back to `getcwd()`), so paths are
reliable regardless of where PHPUnit is launched. An explicit `root` (via `fromArray()` or
`Talon::boot()`) always wins.

---

## Runtime detection

```php
use Phalcon\Talon\Environment;

Environment::phalconAvailable();   // true if the extension OR the PHP implementation is present
Environment::viaExtension();       // true if provided by ext-phalcon
Environment::viaImplementation();  // true if provided by phalcon/phalcon
```

Because both providers expose the same fully qualified class names, all Talon code is
runtime-agnostic. In a test, `AbstractUnitTestCase::checkPhalconAvailable()` skips the suite
when neither provider is present.

---

## Base test cases

### Unit tests

`AbstractUnitTestCase` composes `ReflectionTrait` + `FileSystemTrait` and adds extension
checks.

```php
use Phalcon\Talon\PHPUnit\AbstractUnitTestCase;

final class CalculatorTest extends AbstractUnitTestCase
{
    public function testInternal(): void
    {
        $calc = new Calculator();
        $this->assertSame(5, $this->callProtectedMethod($calc, 'add', 2, 3));
    }
}
```

Available helpers:

| Method | Purpose |
|--------|---------|
| `callProtectedMethod($obj, $method, ...$args)` | invoke a protected/private method |
| `getProtectedProperty($obj, $name)` / `setProtectedProperty($obj, $name, $value)` | read/write protected/private properties (object or class-string) |
| `invokeMethod($object, $name, array $params = [])` | invoke a method with an argument array |
| `getNewFileName($prefix = '', $suffix = 'log')` | unique file name |
| `getDirSeparator($dir)` | directory with a trailing separator |
| `safeDeleteFile($path)` / `safeDeleteDirectory($path)` | delete a file / recursively delete a directory |
| `assertFileContentsContains($file, $needle)` / `assertFileContentsEqual($file, $contents)` | file-content assertions |
| `checkExtensionIsLoaded($ext)` | skip the suite if a PHP extension is missing |
| `checkPhalconAvailable()` | skip the suite if Phalcon is not available |

### Database tests

`AbstractDatabaseTestCase` adds `DatabaseTrait`. The driver comes from the `driver`
environment variable (`sqlite`, `mysql`, `mariadb`, `pgsql`; default `sqlite`); credentials
come from `Settings`.

Normally you set `dump_file` and the schema loads itself the first time a connection is
built, so `setUp()` only seeds data:

```php
use Phalcon\Talon\PHPUnit\AbstractDatabaseTestCase;

final class UserTest extends AbstractDatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->getConnection()->execute("INSERT INTO users (id, email) VALUES (1, 'john.connor@skynet.dev')");
    }

    public function testSeeded(): void
    {
        $this->assertInDatabase('users', ['email' => 'john.connor@skynet.dev']);
        $this->assertNotInDatabase('users', ['id' => 999]);
    }
}
```

| Method | Purpose |
|--------|---------|
| `getConnection()` | the cached `Connection` for the active driver |
| `getDialect()` | the SQL dialect enum - `mariadb` reports `Dialect::Mysql` |
| `getDriver()` | the configured driver name - tells `mariadb` and `mysql` apart |
| `getFromDatabase($table, $criteria)` | rows matching the criteria |
| `assertInDatabase($table, $criteria)` / `assertNotInDatabase(...)` | row-presence assertions |
| `addTable($table)` | create one table from the loaded schema manifest (see [schema fixtures](#schema-fixtures)) |
| `resetConnections()` *(static)* | drop the cached connection (called automatically on `tearDown`) |

### Service tests

`AbstractServicesTestCase` adds `ServicesTrait` for Redis (predis) and Memcached. Tests are
**skipped automatically** when the backend is unreachable.

```php
use Phalcon\Talon\PHPUnit\AbstractServicesTestCase;

final class CacheTest extends AbstractServicesTestCase
{
    public function testRedis(): void
    {
        $this->setRedisKey('key', 'value');
        $this->assertTrue($this->hasRedisKey('key'));
        $this->assertSame('value', $this->getRedisKey('key'));
    }
}
```

Redis: `getRedisKey`, `setRedisKey`, `hasRedisKey`, `doesNotHaveRedisKey`,
`sendRedisCommand`. Memcached: `getMemcachedKey`, `setMemcachedKey`, `hasMemcachedKey`,
`doesNotHaveMemcachedKey`, `clearMemcached`.

### Functional tests

`AbstractFunctionalTestCase` adds `FunctionalTrait`. You supply your configured
application through `appFactory()` - Talon never owns your container. Works with
`Mvc\Application` and `Micro`.

```php
use Phalcon\Talon\PHPUnit\AbstractFunctionalTestCase;

final class HomeTest extends AbstractFunctionalTestCase
{
    protected function appFactory(): callable
    {
        return fn () => require __DIR__ . '/../app/bootstrap.php'; // returns a configured app
    }

    public function testHome(): void
    {
        $this->dispatch('/');
        $this->assertController('index');
        $this->assertAction('index');
        $this->assertResponseContentContains('Welcome');
    }
}
```

| Method | Purpose |
|--------|---------|
| `dispatch($url)` | build the app via `appFactory()` and handle the URL |
| `getContent()` | the response body |
| `assertController($name)` / `assertAction($name)` | dispatcher assertions |
| `assertResponseCode($code)` | the `Status` header contains the code |
| `assertHeader(['Name' => 'Value'])` | response headers |
| `assertRedirectTo($location)` | the `Location` header |
| `assertDispatchIsForwarded()` | the dispatch was forwarded |
| `assertResponseContentContains($needle)` | the body contains a string |

Set `protected bool $resetSuperglobals = true;` to clear `$_GET`/`$_POST`/… on teardown.

### Browser tests

`AbstractBrowserTestCase` composes `BrowserTrait` (actions) and `BrowserAssertionsTrait`
(page assertions) for multi-request flows - login, forms, redirects. It drives your app
**in-process** (no web server) through a `symfony/browser-kit` bridge, so cookies and the
session persist across requests within a test. You supply the app through `appFactory()`,
exactly as for functional tests.

```php
use Phalcon\Talon\PHPUnit\AbstractBrowserTestCase;

final class LoginTest extends AbstractBrowserTestCase
{
    protected function appFactory(): callable
    {
        return fn () => require __DIR__ . '/../app/bootstrap.php';
    }

    public function testLogin(): void
    {
        $this->visitPage('/session/login');
        $this->fillField('email', 'sarah.connor@skynet.dev');
        $this->fillField('password', 'password1');
        $this->pressButton('Log In');            // submits the form; the CSRF token is carried automatically

        $this->assertPageContainsText('Search users');   // redirect followed, session kept
    }
}
```

| Method | Purpose |
|--------|---------|
| `visitPage($url)` | GET a URL |
| `fillField($name, $value)` | set a form input value |
| `selectOption($name, $value)` | select a dropdown/radio option |
| `clickLink($text, $context = null)` | follow an anchor by text, optionally within an XPath context node |
| `pressButton($labelOrSelector)` | submit a form via its button, by label or XPath |
| `getCookie($name)` / `setCookie($name, $value)` | read/write the browser cookie jar |
| `assertPageContainsText($text)` / `assertPageMissingText($text)` | page-text assertions |

Redirects are followed automatically and the session persists across requests, so a login
in one request authenticates the next. A missing link, button, or form raises
`Exceptions\ElementNotFound`. The base pulls in `symfony/browser-kit` + `symfony/dom-crawler`.

---

## Schema fixtures

A schema fixture declares one table's DDL per dialect. Talon generates the SQL artifacts
from them; your tests use the same classes to create, truncate and populate.

```php
use Phalcon\Talon\Database\Schema\AbstractSchema;

final class UsersSchema extends AbstractSchema
{
    protected string $table = 'users';

    public function insert(int $id, string $email): int
    {
        return $this->execute(
            'INSERT INTO users (id, email) VALUES (:id, :email)',
            [':id' => $id, ':email' => $email]
        );
    }

    protected function getStatementsMysql(): array
    {
        return ['CREATE TABLE users (id INT PRIMARY KEY, email VARCHAR(255));'];
    }

    protected function getStatementsPgsql(): array
    {
        return ['CREATE TABLE users (id INT PRIMARY KEY, email VARCHAR(255));'];
    }

    protected function getStatementsSqlite(): array
    {
        return ['CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT);'];
    }
}
```

The three per-dialect methods are abstract on purpose - a new dialect cannot be silently
forgotten. There is no `mariadb` method: MariaDB uses the MySQL dialect.

- **Creation statements only.** Do not write a `DROP TABLE` yourself; the generator
  prepends one from the table name.
- **An empty list means the table does not exist in that dialect.** It is skipped
  entirely, drop included, and gets no manifest entry.
- **`getDependencies()`** returns the table names that must exist first. Override it when
  the table carries a foreign key.
- **`insert()` is yours.** The contract covers the lifecycle - `create()`, `drop()`,
  `clear()` - never the data shape, so each fixture types its own insert signature.
- **One fixture, one table.** The table name is the artifact file name and the manifest
  key, so two fixtures declaring the same table throw `SchemaTableDuplicate`. A fixture
  whose statements create a second table gets no generated `DROP` for it - write that drop
  yourself, or split the fixture in two.

Two interfaces split the two lifetimes a fixture has. `AbstractSchema` implements both:

| Interface | When | Methods |
|-----------|------|---------|
| `Schema\SchemaDefinition` | build time, no connection | `getTable()`, `getStatements(Dialect)`, `getDependencies()` |
| `Schema\SchemaFixture` | run time, needs PDO | `create()`, `drop()`, `clear()` |

### Generating the artifacts

```bash
talon schema             # every dialect
talon schema mysql       # one dialect
```

Configure it with these settings (env vars, or keys in `Settings::fromArray()`):

| Setting | Meaning |
|---------|---------|
| `schema_source` | Directory holding the fixture classes, relative to the project root |
| `schema_namespace` | Namespace prefix for those classes |
| `schema_output` | Directory the artifacts are written to, relative to the project root |
| `schema_pre` | FQCN run before every table - session setup, namespace creation |
| `schema_post` | FQCN run after every table - closes whatever `schema_pre` opened |

Each dialect gets its own directory:

```
schema/mysql/_preSchema.sql     always written, even when empty
schema/mysql/users.sql          one file per table: its DROP, then its creation statements
schema/mysql/manifest.json      load order, dependencies, per-dialect presence
schema/mysql/_postSchema.sql    always written, even when empty
```

Filenames follow your table names verbatim, so casing is mixed and a schema-qualified name
keeps its dot (`private.orders_x_products.sql`). The manifest is **generated, never
hand-edited** - if it is wrong, fix a fixture class and regenerate.

`_preSchema.sql` and `_postSchema.sql` are written even when they have no statements: the
loader must be able to tell "deliberately nothing" from "the generator never ran". A
silently skipped MySQL pre-schema would leave `FOREIGN_KEY_CHECKS` on and fail the bulk
load in ways that point nowhere near the cause.

### Loading

Point `dump_file` at the dialect directory and `AbstractDatabaseTestCase` loads it on the
first connection - `_preSchema.sql`, then the manifest's tables in order, then
`_postSchema.sql`:

```xml
<env name="dump_file" value="resources/schema/mysql"/>
```

`loadSchema()` also still accepts a single flat `.sql` file, so a project can migrate to
the directory format on its own schedule. `SchemaGenerator::generate()` renders that flat
form if you need it.

### `addTable()` for a throwaway table

The default path - load the whole schema once, then truncate - stays the right one: it is
fast, ordered and dependency-safe. `addTable()` is the escape hatch for a test that needs
one table rebuilt, typically a structural test about DDL itself:

```php
$this->addTable('users');
```

It is **standalone only, and that is enforced**. A table's declared dependencies must
already exist, or it throws `Exceptions\SchemaDependencyMissing` naming the missing one.
`addTable()` never resolves dependencies for you - call it once per table, in order:

```php
$this->addTable('users');   // the dependency first
$this->addTable('orders');  // then the dependent
```

The strictness is deliberate. `_preSchema` is live only during the bulk load; by test time
`_postSchema` has restored `FOREIGN_KEY_CHECKS=1`, so a table with foreign keys that loads
fine in bulk can fail standalone on MySQL for reasons nothing at the call site suggests.
Refusing up front beats a dialect-specific driver error.

---

## Mocking a resultset

`ResultSetTrait::mockResultSet()` builds a mocked `Phalcon\Mvc\Model\Resultset` with no
database - handy for testing code that consumes resultsets.

```php
use Phalcon\Talon\Traits\ResultSetTrait;

final class ReportTest extends \PHPUnit\Framework\TestCase
{
    use ResultSetTrait;

    public function testReport(): void
    {
        $resultset = $this->mockResultSet([$modelA, $modelB]);

        $this->assertCount(2, $resultset);
        $this->assertSame($modelA, $resultset->getFirst());
        $this->assertSame($modelB, $resultset->getLast());
    }
}
```

The mock supports `count()`, `getFirst()`, `getLast()`, `toArray()`, and iteration
(`valid`/`key`/`next`). Pass a `Resultset` subclass as the second argument to mimic it; a
non-`Resultset` class throws `Exceptions\InvalidResultsetClass`.

---

## Using the traits directly

The traits are the core public API and carry no base-class requirement for their
non-assertion helpers, so other runners can consume them. With Pest:

```php
uses(Phalcon\Talon\Traits\DatabaseTrait::class);

it('finds the seeded user', function () {
    $this->assertInDatabase('users', ['id' => 1]);
});
```

> Pest and Codeception adapters are planned for a future release; the traits already work
> with both today.

---

## Exceptions

All exceptions implement the `Phalcon\Talon\Contracts\Throwable` marker and extend a single
base `Phalcon\Talon\Exceptions\Exception`, so you can catch any Talon error with one type:

```php
try {
    // ...
} catch (\Phalcon\Talon\Contracts\Throwable $e) {
    // any Talon failure
}
```

| Exception | Thrown when |
|-----------|-------------|
| `InvalidConfiguration` | a required `Settings` key is missing/invalid |
| `UnknownDriver` | a DB driver other than mariadb/mysql/pgsql/sqlite is requested |
| `UnknownSuite` | `talon run` is given a suite name that is not mapped |
| `SchemaFileNotFound` | a configured schema dump file or artifact does not exist |
| `SchemaClassNotFound` | `schema_pre`/`schema_post` names a class that cannot be loaded |
| `SchemaConnectionMissing` | a fixture's `create()`/`drop()` runs with no PDO attached |
| `SchemaManifestNotFound` | a schema directory has no `manifest.json` |
| `SchemaManifestNotLoaded` | `addTable()` is called before any manifest was loaded |
| `SchemaTableNotFound` | `addTable()` names a table absent from the loaded manifest |
| `SchemaTableDuplicate` | two schema fixtures declare the same table for one dialect |
| `SchemaDependencyMissing` | `addTable()` runs before one of the table's dependencies exists |
| `InvalidApplication` | `appFactory()` returns something without `handle()` |
| `ResponseNotDispatched` | a response/dispatch assertion runs before `dispatch()` |
| `MissingService` | the app's DI lacks the `dispatcher` an assertion needs |
| `ElementNotFound` | a browser `clickLink()`/`pressButton()`/form helper can't find the element |
| `InvalidResultsetClass` | `mockResultSet()` is given a non-`Resultset` class |
| `PhalconNotAvailable` | neither Phalcon provider is present (engine layer) |

---

## Engine reference

These plain classes are usable anywhere, not just inside a `TestCase`.

| Class | Key API |
|-------|---------|
| `Settings` | `fromArray()`, `fromEnv()`, `rootPath()`/`outputPath()`/…, `getDatabaseDsn()`, `getDatabaseOptions()`, `getServiceOptions()`, `get()` |
| `Environment` | `phalconAvailable()`, `viaExtension()`, `viaImplementation()` |
| `Database\Connection` | `getPdo()`, `loadSchema($fileOrDirectory)`, `addTable($table)`, `tableExists($table)`, `select($table, $criteria)`, `execute($sql)` |
| `Database\Dialect` | enum `Mysql`/`Pgsql`/`Sqlite`; `fromPdo(PDO)`, `quoteIdentifier($name)` |
| `Database\StatementSplitter` | `split($sql): array` - splits a dump into statements (handles `DELIMITER` and pgsql `$$` blocks) |
| `Database\Schema\AbstractSchema` | base fixture: `create()`, `drop()`, `clear()`, `getStatements(Dialect)`, `setConnection(PDO)` |
| `Database\Schema\SchemaCollector` | `definitions()`, `preSchema()`, `postSchema()` - discovers fixtures in a directory |
| `Database\Schema\SchemaGenerator` | `generate(Dialect)` (flat), `preSchema()`, `table()`, `postSchema()` |
| `Database\Schema\SchemaWriter` | `write(Dialect, $directory)` - per-table files plus the manifest |
| `Database\Schema\SchemaManifest` | `encode()`, `fromDirectory()`, `getTables()`, `getDependencies()`, `getFile()` |
| `Bootstrap\Runner` | `for()`, `before()`, `after()`, `boot()` |
| `Bootstrap\DiFactory` | `create(?callable $register = null): DiInterface` - a `FactoryDefault` seeded with `config` |
| `Talon` | `boot()`, `useSettings()`, `settings()`, `reset()` |

Swappable contracts live under `Phalcon\Talon\Contracts\`: `Settings`, `Connection`,
`Bootstrap`, `Throwable`.

---

## Running the test suite

Composer scripts (names follow `pds/composer-script-names`):

```bash
composer test                 # unit + sqlite
composer test-coverage        # clover report -> tests/_output/coverage.xml
composer test-coverage-html   # html report -> tests/_output/coverage
composer analyze              # phpstan (max level)
composer cs                   # phpcs (PSR-12)
composer cs-fix               # phpcbf
composer cs-fixer             # php-cs-fixer (dry-run)
composer cs-fixer-fix         # php-cs-fixer (apply)
```

`composer test` runs without any database server. The two coverage scripts run the `unit`,
`mariadb`, `mysql` and `pgsql` suites and merge the results, so they **do** need live
MariaDB, MySQL and PostgreSQL servers.

### The `talon` command

```bash
talon run                     # the default suite
talon run unit sqlite         # named suites; 'all' runs every mapped suite
talon run unit -- --filter Foo   # everything after -- goes to PHPUnit verbatim
talon suites                  # list the mapped suites
talon schema [drivers...]     # generate the schema artifacts
talon --help | --version
```

Suites are resolved from a `talon.php` map at the project root, or - with zero
configuration - discovered from the `phpunit*.xml` files themselves
(`phpunit.mysql.xml` -> `mysql`; `phpunit.xml.dist` -> `unit`, the default). An unmapped
name throws `Exceptions\UnknownSuite`.

### Dockerized environments

The repository ships containers under `resources/docker/` and a root `docker-compose.yml`
that run the suite across the support matrix without local infrastructure. See
[`CONTRIBUTING.md`](../CONTRIBUTING.md) for the full local-development guide; the essentials:

```bash
cp resources/.env.example .env
sed -i "s/^UID=.*/UID=$(id -u)/;s/^GID=.*/GID=$(id -g)/" .env   # match your host user

# one-time: install dependencies (writes vendor to your checkout)
docker compose run --rm app composer install

# one-off commands
docker compose run --rm app composer test                              # unit + sqlite
docker compose run --rm app vendor/bin/phpunit -c resources/phpunit.mysql.xml
docker compose run --rm app vendor/bin/phpunit -c resources/phpunit.mariadb.xml
docker compose run --rm app vendor/bin/phpunit -c resources/phpunit.pgsql.xml

# or work inside a long-lived container
docker compose up -d
docker compose exec app composer test
```

The image's PHP version and Phalcon provider are build arguments: `PHP_VERSION`
(8.1-8.5) and `PHALCON_VARIANT` (`v5` = C extension via PIE, `v6` = the `phalcon/phalcon`
package). When you switch either value locally, rebuild and re-install (there are no named
volumes - dependencies live in your checkout): `docker compose up -d --build`, then
`docker compose run --rm app composer install`.

---

## Environment variables

Read by `Settings::fromEnv()` (and the per-driver PHPUnit configs):

| Variable | Default | Used for |
|----------|---------|----------|
| `driver` | `sqlite` | active DB driver (`sqlite`, `mysql`, `mariadb`, `pgsql`) |
| `DATA_MARIADB_HOST` / `_PORT` / `_USER` / `_PASS` / `_NAME` / `_CHARSET` | 127.0.0.1 / 3306 / root / "" / talon / utf8mb4 | MariaDB connection (independent of MySQL) |
| `DATA_MYSQL_HOST` / `_PORT` / `_USER` / `_PASS` / `_NAME` / `_CHARSET` | 127.0.0.1 / 3306 / root / "" / talon / utf8mb4 | MySQL connection |
| `DATA_POSTGRES_HOST` / `_PORT` / `_USER` / `_PASS` / `_NAME` / `_SCHEMA` | 127.0.0.1 / 5432 / postgres / "" / talon / "" | PostgreSQL connection |
| `DATA_SQLITE_NAME` | `:memory:` | SQLite database |
| `DATA_REDIS_HOST` / `_PORT` / `_NAME` | 127.0.0.1 / 6379 / 0 | Redis connection |
| `DATA_MEMCACHED_HOST` / `_PORT` / `_WEIGHT` | 127.0.0.1 / 11211 / 0 | Memcached connection |
| `DATA_REDIS_CLUSTER_HOSTS` / `_AUTH` | "" / "" | Redis Cluster connection (`HOSTS` is comma-separated) |
| `DATA_BEANSTALKD_HOST` / `_PORT` | "" / "" | Beanstalkd connection |
| `dump_file` | "" | schema artifact, auto-loaded the first time a connection is built for a driver - a dialect directory (`resources/schema/mysql`) or a flat `.sql` file |
| `initial_queries` | "" | SQL run immediately after connecting, before any other statement |
| `schema_source` | "" | directory holding the schema fixture classes, relative to the project root |
| `schema_namespace` | "" | namespace prefix for those classes |
| `schema_output` | "" | directory `talon schema` writes the artifacts to, relative to the project root |
| `schema_pre` | "" | FQCN emitted before every table |
| `schema_post` | "" | FQCN emitted after every table |

Docker-only variables (`docker-compose.yml`): `PROJECT_PREFIX`, `PHP_VERSION`,
`PHALCON_VARIANT`, `UID`, and `GID`. The backing services are gated by Compose
`depends_on: service_healthy`, so the app container starts once the databases are ready.

---

## License

BSD-3-Clause. See [LICENSE](../LICENSE).
