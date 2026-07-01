# Changelog

## [Unreleased]

### Changed

- **Breaking:** `Settings::getMemcachedOptions()`, `getRedisOptions()`, and `getRedisClusterOptions()` are removed. Use `getServiceOptions('memcached')`, `getServiceOptions('redis')`, and `getServiceOptions('redisCluster')` instead — same return shape, same values, no other change needed at call sites.
- `Settings`'s internal storage is unified: `db`'s three drivers (`mysql`/`pgsql`/`sqlite`) and every other service (`redis`/`memcached`/`redisCluster`/`beanstalk`) are now all stored as named `ServiceOptions` instances in one collection. `getDatabaseOptions(string $driver)`/`getDatabaseDsn(string $driver)` keep their exact existing public signatures and behavior (including throwing `UnknownDriver` for unsupported names) — only their internals changed.
- `Settings::fromArray()`'s config shape: the separate top-level `'redis'`/`'memcached'`/`'redisCluster'` keys are replaced by one `'services' => ['redis' => [...], 'memcached' => [...], 'redisCluster' => [...], 'beanstalk' => [...]]` section (matching how `beanstalk` already worked). Each entry may be a raw options array or an already-constructed `ServiceOptions` instance. `'db' => [...]` keeps its existing shape unchanged.

### Added

- `Phalcon\Talon\ServiceOptions` (new) — a small value object (`getName(): string`, `getOptions(): array`) used internally by `Settings` to represent every named service's (including each db driver's) options uniformly.
- `Settings::fromEnv()` now reads `dump_file` and `initial_queries` (no `DATA_*` prefix, matching the existing lowercase `driver` env-var convention `DatabaseTrait` already used) into the settings' extra-config bag, retrievable via `Settings::get()`.
- `Database\Connection` now applies `PRAGMA journal_mode = WAL` for the sqlite driver and runs an optional `initial_queries` SQL string (from `Settings::get('initial_queries')`) immediately after connecting, before any other statement.
- `Traits\DatabaseTrait::getConnection()` now automatically loads the schema from `Settings::get('dump_file')` the first time a connection is built for a given driver, guarded by the same per-driver cache used for connection reuse. No-op when `dump_file` is unset (fully backward compatible).
- `Settings::getDatabaseOptions('pgsql')` now includes a `schema` key, read from `DATA_POSTGRES_SCHEMA`.
- `Traits\DatabaseTrait::getDriver(): string` (new, public) exposes the driver `getConnection()` already resolves internally.
- `Settings::getServiceOptions(string $name): array` (new, also on `Contracts\Settings`) — a generic, key/name-based accessor for simple `host`/`port`-shaped third-party services, reading `redis`, `memcached`, `redisCluster`, `beanstalk`, and each db driver's options. `fromEnv()` populates it from a small internal declarative table; `fromArray()` reads a `services` section shaped the same way `db` is.
- `Bootstrap\Runner::initEnvironment()` now also sets `display_errors`/`display_startup_errors`, a `en_US.utf-8` locale, the `mbstring` internal encoding default, clears the stat cache, and (when the `xdebug` extension is loaded) tunes `xdebug.cli_color`/`xdebug.dump_globals`/`xdebug.show_local_vars`/`xdebug.max_nesting_level`/`xdebug.var_display_max_depth` for readable CLI debugging. `error_reporting(E_ALL)` and the `UTC` timezone it already set are unchanged. (`mb_substitute_character('none')` was deliberately *not* ported despite being part of the source this was consolidated from — it makes `mb_convert_encoding()` silently drop unrepresentable characters instead of substituting `?`, which conflicts with at least one real test's expectations.)

### Fixed

### Removed

## [0.4.0](https://github.com/phalcon/talon/releases/tag/v0.4.0) (2026-06-29)

### Changed

### Added

- `Browser\Client` now emits the application's response cookies as `Set-Cookie` headers, so cookies set through the Phalcon cookies service round-trip through the BrowserKit cookie jar between in-process requests (and cookie deletions evict them). The application under test must run with cookie encryption disabled.

### Fixed

- `Browser\Client` caps redirect-following (`MAX_REDIRECTS`); an in-process redirect cycle now raises a clean `LogicException` instead of recursing until the runtime's stack overflows. The previous default (`-1`, unbounded) could segfault the Phalcon extension on a redirect loop.
- `BrowserTrait::setCookie()` scopes the cookie to the request host, so a cookie set in a test can be expired by an application response — an empty-domain cookie (the previous default) could never be cleared.

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
