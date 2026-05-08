# MDCenter

## Overview
The MDCenter serves as the administrative portal for your Moodle instances.

It is built on Laravel 10 and Filament v3.

## Installation

### Local environment

- Create `.env` in the project root folder.
- Create `.env` in the project `/web` folder (see `.env.example` for example values).
- Run `docker-compose -f local.yml up -d` command.
- Run `composer install` command.
- Open container bash with `docker exec -it map-php bash` command & run `php artisan migrate` to migrate database tables.
- Create storage symlink with `php artisan storage:link` (if symlink don't work you can add --relative flag).

## Code Style

Run `./vendor/bin/pint` before every commit to follow required code style.

## Tests

Run the regular test suite with:

```bash
php artisan test
```

### Coverage in DDEV

Use DDEV's built-in Xdebug support for coverage runs:

```bash
ddev xdebug on
ddev exec composer test:coverage
ddev xdebug off
```

This keeps the normal test workflow fast while making coverage runs repeatable inside the containerized environment.
The coverage command intentionally uses PHPUnit directly, because that is the verified CLI path that prints the text coverage summary inside DDEV.
