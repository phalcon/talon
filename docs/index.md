# Phalcon Talon

Test harness and Phalcon bootstrapping for PHPUnit and beyond.

See the [README](../README.md) for the full quickstart: installation, the `Talon::boot()`
one-liner and bootstrap hooks, and the unit / database / functional / service test base
classes.

## Layers

- **Engine** (`Settings`, `Database\Connection`, `Bootstrap\Runner`, `Bootstrap\DiFactory`,
  `Environment`, `Talon`) - framework-neutral, no PHPUnit dependency.
- **Traits** (`Reflection`, `FileSystem`, `Database`, `Services`, `ResultSet`, `Functional`)
  — the core public API.
- **PHPUnit bases** (`AbstractUnitTestCase`, `AbstractDatabaseTestCase`,
  `AbstractServicesTestCase`, `AbstractFunctionalTestCase`) - thin convenience classes.
