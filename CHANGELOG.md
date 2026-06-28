# Changelog

## [0.2.0](https://github.com/phalcon/talon/releases/tag/v0.2.0) (2026-xx-xx)

### Changed

### Added

- `AbstractUnitTestCase::mockWithoutConstructor()` / `mockWithConstructor()` - build a test
  double with the constructor disabled or invoked (with constructor arguments), keeping the
  non-overridden methods real. Method overrides become stubs (a Closure body, a return value,
  or `null` for a type-safe default) and property overrides are set via reflection. Built as a
  stub on PHPUnit 12+ (so no "mock object without expectations" notice is raised); works across
  PHPUnit 10.5-13.

### Fixed

- `ServicesTrait` now skips (instead of erroring) when the backing client is unavailable: the
  memcached helpers skip when `ext-memcached` is not loaded, and the redis helpers skip when the
  `predis/predis` package is not installed.

### Removed

## [0.1.0](https://github.com/phalcon/talon/releases/tag/v0.1.0) (2026-06-27)

### Changed

### Added

- Initial Talon package: framework-neutral engine (`Settings`, `Environment`,
  `Database\Connection`, `Database\StatementSplitter`, `Bootstrap\Runner`,
  `Bootstrap\DiFactory`, `Talon` facade), the six test traits (`Reflection`, `FileSystem`,
  `Database`, `Services`, `ResultSet`, `Functional`), PHPUnit `Abstract*TestCase` bases,
  granular `Exceptions\*` with a `Contracts\Throwable` contract, and dockerized test
  environments (PHP 8.1-8.4 × Phalcon v5 extension / v6 implementation, with
  mysql/pgsql/redis/memcached backends).

### Fixed

### Removed
