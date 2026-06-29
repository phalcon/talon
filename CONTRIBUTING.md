# Contributing to Phalcon Talon

Thanks for helping improve Talon. Development runs entirely in Docker - you need only
**Docker** and **Docker Compose**, no local PHP, Phalcon, or database setup.

## Setup

```bash
git clone https://github.com/phalcon/talon.git
cd talon

cp resources/.env.example .env
# Match the container user to your host so bind-mounted writes (tests/_output) work:
sed -i "s/^UID=.*/UID=$(id -u)/;s/^GID=.*/GID=$(id -g)/" .env

# Build the image and install dependencies (vendor is written to your checkout):
docker compose run --rm app composer install
```

The image is built from `resources/docker/Dockerfile`. Two build arguments select the
runtime (both have defaults in `.env`):

- `PHP_VERSION` - `8.1`–`8.5`.
- `PHALCON_VARIANT` - `v5` (the Phalcon C extension) or `v6` (the `phalcon/phalcon` package).

For the **v6** variant, also pull in the PHP implementation after installing:

```bash
docker compose run --rm app composer require --dev "phalcon/phalcon:^6@dev"
```

When you change `PHP_VERSION` or `PHALCON_VARIANT`, rebuild and re-install — there are no
named volumes to reset, dependencies live in your checkout:

```bash
docker compose up -d --build
docker compose run --rm app composer install
```

## Running things

Two equivalent workflows - use whichever you prefer.

### Inside the container (interactive)

Bring the stack up once - Compose starts MySQL/PostgreSQL/Redis/Memcached and waits for the
databases to be healthy - then work inside the long-lived `app` container:

```bash
docker compose up -d
docker compose exec app bash      # a shell in /srv

# then, inside the container:
composer test
composer analyze
```

### One-off commands (from the host)

```bash
docker compose run --rm app composer test
docker compose run --rm app composer analyze
```

Stop the stack with `docker compose down`.

## Commands

| Command | What it does |
|---------|--------------|
| `composer test` | the default suite (unit, functional, browser, sqlite) |
| `composer test-coverage` | Clover report → `tests/_output/coverage.xml` |
| `composer test-coverage-html` | HTML report → `tests/_output/coverage` |
| `composer analyze` | PHPStan (max level) |
| `composer cs` | PHP_CodeSniffer (PSR-12) |
| `composer cs-fix` | PHPCBF (apply coding-standard fixes) |
| `composer cs-fixer` | PHP-CS-Fixer (dry-run) |
| `composer cs-fixer-fix` | PHP-CS-Fixer (apply) |

The MySQL and PostgreSQL suites use dedicated configs:

```bash
docker compose run --rm app vendor/bin/phpunit -c resources/phpunit.mysql.xml
docker compose run --rm app vendor/bin/phpunit -c resources/phpunit.pgsql.xml
```

## Before opening a pull request

- `composer test` is green.
- `composer analyze`, `composer cs`, and `composer cs-fixer` are clean.
- New code is covered - the suite holds 100% line/method coverage.
- Code follows PSR-12, and `use` statements, properties, and methods are ordered
  alphabetically within their visibility group.

See [`docs/index.md`](docs/index.md) for the full reference.
