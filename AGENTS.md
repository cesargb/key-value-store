# Repository Guidelines

## Project Structure & Module Organization
- `src/` contains the library code under `Cesargb\KeyValueStore\`.
- `src/Repositories/` holds backend implementations (`ArrayStore`, `FileStore`, `RedisStore`, `PredisStore`, `EloquentStore`).
- `src/Contracts/` defines interfaces; `src/Exceptions/` stores domain exceptions.
- `tests/` mirrors runtime behavior with `*Test.php` files and backend-specific tests in `tests/Repositories/`.
- `stubs/` contains PHPStan support stubs (currently `predis.stub.php`).
- `.github/workflows/` defines CI for lint, static analysis, and tests.

## Build, Test, and Development Commands
- `composer install` installs dependencies.
- `./vendor/bin/phpunit` runs the full test suite from `phpunit.xml.dist`.
- `./vendor/bin/phpstan analyse` runs static analysis on `src/` (level 5).
- `pint --test` checks formatting (used in CI; install Pint locally if missing).
- Optional integration dependencies:
  - `composer require predis/predis` for `PredisStore` tests.
  - Enable `ext-redis` for `RedisStore` tests.

## Coding Style & Naming Conventions
- Follow PSR-12 style with 4-space indentation and typed properties/methods.
- Use `StudlyCase` for classes, `camelCase` for methods/properties, and descriptive test names like `test_set_and_get`.
- Keep namespaces PSR-4 aligned with paths (for example, `Cesargb\KeyValueStore\Repositories\FileStore`).
- Prefer small, focused repository classes that implement `Cesargb\KeyValueStore\Contracts\Store`.

## Testing Guidelines
- Framework: PHPUnit 12.
- Place tests in `tests/` and suffix with `Test.php`.
- Cover happy path, edge cases, and validation failures (see key validation tests in `tests/StoreTest.php`).
- For backend repositories, add dedicated tests under `tests/Repositories/`.

## Commit & Pull Request Guidelines
- Commit style in history is imperative and scoped, for example: `Add FileStore repository with file-based persistence and full test coverage`.
- Keep commits focused; include tests with behavioral changes.
- PRs should include:
  - concise summary of changed behavior,
  - linked issue (if applicable),
  - notes on dependency/extension requirements (`predis`, `ext-redis`, DB setup),
  - confirmation that `phpunit`, `phpstan`, and lint checks pass.

## Security & Configuration Tips
- Never commit secrets or environment-specific credentials.
- For file storage tests, use temporary directories and avoid writing outside project-safe paths.
- Validate keys consistently (`{ } ( ) / \\ @ :` are reserved) before persistence operations.
