# Changelog

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
