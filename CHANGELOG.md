# Changelog

## [0.8.0](https://github.com/phalcon/talon/releases/tag/v0.8.0) (2026-07-15)

### Changed

### Added

- Added a REST/JSON test surface: `Phalcon\Talon\Traits\RestTrait` (the full verb set, request headers that persist across requests, `amBearerAuthenticated()`/`amHttpAuthenticated()`, redirect control, and response grabbers), `Phalcon\Talon\Traits\RestAssertionsTrait` (status, range, body, header, and JSON assertions), and `Phalcon\Talon\PHPUnit\AbstractRestTestCase` which composes both. [#19](https://github.com/phalcon/talon/issues/19)
- Added `Phalcon\Talon\Http\HttpCode` - HTTP status constants plus `getDescription()`, which returns the `404 (Not Found)` form. It is deliberately an independent implementation of the standard reason phrases rather than a lookup into the application under test, so that asserting an application's emitted status string against it actually asserts something. [#19](https://github.com/phalcon/talon/issues/19)
- Added `Phalcon\Talon\Http\JsonType` - validates a decoded JSON document against a map of type expectations (`string`, `integer`, `float`, `boolean`, `array`, `null`, the `:date` filter, `|` unions, and nested maps). Keys absent from the map are ignored. Types are strict: an int does not satisfy `float`. [#19](https://github.com/phalcon/talon/issues/19)
- Added `Phalcon\Talon\Http\JsonSubset` - recursive subset matching, so a fragment can be asserted against a full document. Keys and list elements present in the response but absent from the expectation are ignored, and list elements match in any order. [#19](https://github.com/phalcon/talon/issues/19)
- Added `TALON_REST_URL` to `Settings::fromEnv()`, readable via `Settings::get('rest_url')` and defaulting to `http://127.0.0.1:8080`. [#19](https://github.com/phalcon/talon/issues/19)

### Fixed

### Removed

## [0.7.0](https://github.com/phalcon/talon/releases/tag/v0.7.0) (2026-07-10)

### Changed

### Added

### Fixed

- `AbstractUnitTestCase::setUp()` now guards its `Di::reset()` call behind `phalconAvailable()`, so packages that use the Talon abstract test cases without Phalcon (or without the DI component) no longer hit a fatal `Class "Phalcon\Di\Di" not found` at setup time - because `AbstractUnitTestCase` is the root of the hierarchy, every abstract (`AbstractServicesTestCase`/`AbstractDatabaseTestCase`/`AbstractBrowserTestCase`/`AbstractFunctionalTestCase`) inherits the fix and can be extended without a DI. When Phalcon is available the reset still runs unchanged. [#14](https://github.com/phalcon/talon/issues/14)
- `AbstractBrowserTestCase` now clears `$_SESSION` in `tearDown()` as well as `setUp()`, so a test that logs a user in no longer leaks that session into a following test that does not reset it itself. The browser fixture session persists across the in-process app rebuilds and `Di::reset()` does not clear it, which surfaced as a random-order failure of `FixtureSmokeTest::testSecuredShowsGuestWithoutSession` (it read a `sarah` session left behind by `BrowserTraitTest`). [#16](https://github.com/phalcon/talon/pull/16)

### Removed

## [0.6.0](https://github.com/phalcon/talon/releases/tag/v0.6.0) (2026-07-02)

### Changed

- The repo dogfoods its own runner: `composer test`/`test-coverage`/`test-coverage-html` and the CI database step now route through `bin/talon` (`php bin/talon run mysql pgsql` replaces the two explicit PHPUnit invocations). Infection still drives PHPUnit directly by design; the raw `vendor/bin/phpunit -c ...` invocations keep working as a fallback. [#5](https://github.com/phalcon/talon/issues/5)

### Added

- `talon` CLI runner: `vendor/bin/talon run [suites...] [-- passthrough]` fronts PHPUnit per mapped suite, `talon suites` lists the map. Suites come from a root `talon.php` (per-suite `config`/`php`/`env`/`args`, global `php`/`env` merged in) or, with zero config, from discovered `phpunit*.xml` files (`phpunit.mysql.xml` -> `mysql`; `phpunit.xml.dist` -> `unit`, the default). Options forward to PHPUnit starting at the first option talon does not recognize (everything after `--` always forwards verbatim); the reserved name `all` runs every suite sequentially with a `max()` exit code; subprocess re-exec keeps per-suite ini flags (e.g. `extension=phalcon.so`) and env vars possible. Adds `phalcon/cli-options-parser` (^2.0) as a runtime dependency. [#5](https://github.com/phalcon/talon/issues/5)
- Infection mutation testing: `infection/infection` (^0.29) as a dev dependency, configured via `resources/infection.json5` (source `src/`, `@default` mutators, logs and temp files under `tests/_output/infection/`), with a `composer test-mutation` script and a report-only step in the CI coverage job. The suite was hardened until the mutation score plateaued at 99% MSI / 99% covered MSI under the ext-phalcon (v5) test image - the five surviving mutants are provider-specific and are killed under the phalcon/phalcon (v6) provider the CI step runs on. Every config-level mutator ignore is individually justified in `resources/infection.json5`. [#7](https://github.com/phalcon/talon/issues/7)

### Fixed

- `AbstractUnitTestCase::mockWithConstructor()`'s `$ctorArgs` docblock type widened from `array<int, mixed>` to `array<array-key, mixed>` - the implementation always normalized string-keyed arguments via `array_values()`; the annotation now matches. No behavior change - static-analysis-only fix. [#7](https://github.com/phalcon/talon/issues/7)
- `ServicesTrait::setMemcachedKey()`, `setRedisKey()`, and `clearMemcached()` now assert the backing operation succeeded (a failed `Memcached::set()`/`flush()` or a non-`OK` Redis `SET` reply reports as a test failure with the key in the message) instead of silently discarding the result - a failed seed write could previously let a test pass without exercising its intent. [#10](https://github.com/phalcon/talon/issues/10)
- `ServicesTrait::hasMemcachedKey()` (and `doesNotHaveMemcachedKey()`) now checks `Memcached::getResultCode()` against `RES_NOTFOUND`, so a stored literal `false` is correctly reported as present - previously indistinguishable from a missing key. [#10](https://github.com/phalcon/talon/issues/10)
- `FileSystemTrait::safeDeleteFile()` and `safeDeleteDirectory()` still tolerate a missing target, but when it exists every `unlink()`/`rmdir()` is now asserted - a failed delete reports as a clean test failure with the path (instead of a PHP warning) rather than silently leaking state into subsequent tests. [#10](https://github.com/phalcon/talon/issues/10)

### Removed

## [0.5.0](https://github.com/phalcon/talon/releases/tag/v0.5.0) (2026-07-01)

### Changed

- `Settings::getMemcachedOptions()`, `getRedisOptions()`, and `getRedisClusterOptions()` are removed. Use `getServiceOptions('memcached')`, `getServiceOptions('redis')`, and `getServiceOptions('redisCluster')` instead - same return shape, same values, no other change needed at call sites.
- `Settings`'s internal storage is unified: `db`'s three drivers (`mysql`/`pgsql`/`sqlite`) and every other service (`redis`/`memcached`/`redisCluster`/`beanstalk`) are now all stored as named `ServiceOptions` instances in one collection. `getDatabaseOptions(string $driver)`/`getDatabaseDsn(string $driver)` keep their exact existing public signatures and behavior (including throwing `UnknownDriver` for unsupported names) - only their internals changed.
- `Settings::fromArray()`'s config shape: the separate top-level `'redis'`/`'memcached'`/`'redisCluster'` keys are replaced by one `'services' => ['redis' => [...], 'memcached' => [...], 'redisCluster' => [...], 'beanstalk' => [...]]` section (matching how `beanstalk` already worked). Each entry may be a raw options array or an already-constructed `ServiceOptions` instance. `'db' => [...]` keeps its existing shape unchanged.

### Added

- `Phalcon\Talon\ServiceOptions` (new) - a small value object (`getName(): string`, `getOptions(): array`) used internally by `Settings` to represent every named service's (including each db driver's) options uniformly.
- `Settings::fromEnv()` now reads `dump_file` and `initial_queries` (no `DATA_*` prefix, matching the existing lowercase `driver` env-var convention `DatabaseTrait` already used) into the settings' extra-config bag, retrievable via `Settings::get()`.
- `Database\Connection` now applies `PRAGMA journal_mode = WAL` for the sqlite driver and runs an optional `initial_queries` SQL string (from `Settings::get('initial_queries')`) immediately after connecting, before any other statement.
- `Traits\DatabaseTrait::getConnection()` now automatically loads the schema from `Settings::get('dump_file')` the first time a connection is built for a given driver, guarded by the same per-driver cache used for connection reuse. No-op when `dump_file` is unset (fully backward compatible).
- `Settings::getDatabaseOptions('pgsql')` now includes a `schema` key, read from `DATA_POSTGRES_SCHEMA`.
- `Traits\DatabaseTrait::getDriver(): string` (new, public) exposes the driver `getConnection()` already resolves internally.
- `Settings::getServiceOptions(string $name): array` (new, also on `Contracts\Settings`) - a generic, key/name-based accessor for simple `host`/`port`-shaped third-party services, reading `redis`, `memcached`, `redisCluster`, `beanstalk`, and each db driver's options. `fromEnv()` populates it from a small internal declarative table; `fromArray()` reads a `services` section shaped the same way `db` is.
- `Bootstrap\Runner::initEnvironment()` now also sets `display_errors`/`display_startup_errors`, a `en_US.utf-8` locale, the `mbstring` internal encoding default, clears the stat cache, and (when the `xdebug` extension is loaded) tunes `xdebug.cli_color`/`xdebug.dump_globals`/`xdebug.show_local_vars`/`xdebug.max_nesting_level`/`xdebug.var_display_max_depth` for readable CLI debugging. `error_reporting(E_ALL)` and the `UTC` timezone it already set are unchanged. (`mb_substitute_character('none')` was deliberately *not* ported despite being part of the source this was consolidated from - it makes `mb_convert_encoding()` silently drop unrepresentable characters instead of substituting `?`, which conflicts with at least one real test's expectations.)
- `Bootstrap\Runner::isExtensionLoaded(string $extension): bool` (new, protected) - wraps the `extension_loaded()` check `initEnvironment()`'s xdebug tuning uses, so a subclass can fake it in tests.
- `Traits\FunctionalTrait::resolveDi(InjectionAwareInterface $application): DiInterface` (new, protected) - factored out of the internal `di()` helper so a subclass can fake DI resolution in tests, without needing a real `InjectionAwareInterface::getDI()` implementation that returns `null` (not expressible on every Phalcon provider - see Fixed).

### Fixed

- `Traits\FunctionalTrait`'s internal `di()` helper now throws `Exceptions\ResponseNotDispatched` (matching its existing precondition-failure behavior elsewhere in the same method) instead of returning a `null` DI silently typed as non-nullable - `InjectionAwareInterface::getDI()` can genuinely return `null`, which was previously unaccounted for.
- `Traits\ResultSetTrait::mockResultSet()`'s return type now fully specifies `Phalcon\Mvc\Model\Resultset`'s and `Phalcon\Mvc\ModelInterface`'s generic type parameters. No behavior change - static-analysis-only fix.

### Removed

## [0.4.0](https://github.com/phalcon/talon/releases/tag/v0.4.0) (2026-06-29)

### Changed

### Added

- `Browser\Client` now emits the application's response cookies as `Set-Cookie` headers, so cookies set through the Phalcon cookies service round-trip through the BrowserKit cookie jar between in-process requests (and cookie deletions evict them). The application under test must run with cookie encryption disabled.

### Fixed

- `Browser\Client` caps redirect-following (`MAX_REDIRECTS`); an in-process redirect cycle now raises a clean `LogicException` instead of recursing until the runtime's stack overflows. The previous default (`-1`, unbounded) could segfault the Phalcon extension on a redirect loop.
- `BrowserTrait::setCookie()` scopes the cookie to the request host, so a cookie set in a test can be expired by an application response - an empty-domain cookie (the previous default) could never be cleared.

### Removed

## [0.3.0](https://github.com/phalcon/talon/releases/tag/v0.3.0) (2026-06-29)

### Changed

- `AbstractUnitTestCase::setUp()` now resets the default Phalcon DI for per-test isolation.
- The functional base is split into `FunctionalTrait` (actions) and `FunctionalAssertionsTrait` (assertions)
- The containerized dev/test environment now covers PHP 8.1-8.5 for both Phalcon variants (`v5` C extension, `v6` the `phalcon/phalcon` package) built via PIE, with database readiness handled by Docker Compose service healthchecks. [#4](https://github.com/phalcon/talon/issues/4) 

### Added

- `AbstractBrowserTestCase` / `BrowserTrait` / `BrowserAssertionsTrait`: in-process browser testing (navigation, forms, CSRF, redirect-following, session continuity, cookie jar) built on `symfony/browser-kit` + `symfony/dom-crawler`. [#4](https://github.com/phalcon/talon/issues/4)
- `Browser\Client`: an in-process `symfony/browser-kit` client that dispatches requests directly into the Phalcon application without an HTTP server. [#4](https://github.com/phalcon/talon/issues/4)
- `Exceptions\ElementNotFound`. [#4](https://github.com/phalcon/talon/issues/4)
- `CONTRIBUTING.md` with Docker-based development and testing instructions. [#4](https://github.com/phalcon/talon/issues/4)

### Fixed

- `ServicesTrait` now detects an unreachable Memcached backend via `getStats()` (treating `false` or an all-empty stats array as unavailable) instead of `getVersion()`, so the Memcached helpers skip reliably when the server is down. [#4](https://github.com/phalcon/talon/issues/4)

### Removed

- `bin/docker-entrypoint.sh` and `bin/wait-for-db`, superseded by Docker Compose service healthchecks and a keepalive container `CMD`. [#4](https://github.com/phalcon/talon/issues/4)

## [0.2.0](https://github.com/phalcon/talon/releases/tag/v0.2.0) (2026-06-27)

### Changed

### Added

- `AbstractUnitTestCase::mockWithoutConstructor()` / `mockWithConstructor()` - build a test double with the constructor disabled or invoked (with constructor arguments), keeping the non-overridden methods real. Method overrides become stubs (a Closure body, a return value, or `null` for a type-safe default) and property overrides are set via reflection. Built as a stub on PHPUnit 12+ (so no "mock object without expectations" notice is raised); works across PHPUnit 10.5-13.

### Fixed

- `ServicesTrait` now skips (instead of erroring) when the backing client is unavailable: the memcached helpers skip when `ext-memcached` is not loaded, and the redis helpers skip when the `predis/predis` package is not installed.

### Removed

## [0.1.0](https://github.com/phalcon/talon/releases/tag/v0.1.0) (2026-06-27)

### Changed

### Added

- Initial Talon package: framework-neutral engine (`Settings`, `Environment`, `Database\Connection`, `Database\StatementSplitter`, `Bootstrap\Runner`, `Bootstrap\DiFactory`, `Talon` facade), the six test traits (`Reflection`, `FileSystem`, `Database`, `Services`, `ResultSet`, `Functional`), PHPUnit `Abstract*TestCase` bases, granular `Exceptions\*` with a `Contracts\Throwable` contract, and dockerized test environments (PHP 8.1-8.4 × Phalcon v5 extension / v6 implementation, with mysql/pgsql/redis/memcached backends).

### Fixed

### Removed
