# Laravel Soft Deletes Value
<p>
<a href="https://packagist.org/packages/to-mo-ki/laravel-soft-deletes-value"><img src="https://img.shields.io/packagist/dt/to-mo-ki/laravel-soft-deletes-value" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/to-mo-ki/laravel-soft-deletes-value"><img src="https://img.shields.io/packagist/v/to-mo-ki/laravel-soft-deletes-value" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/to-mo-ki/laravel-soft-deletes-value"><img src="https://img.shields.io/packagist/l/to-mo-ki/laravel-soft-deletes-value" alt="License"></a>
</p>

Allow customizing the "undeleted" (active) value for Laravel Soft Deletes. 

Currently, Laravel's native Soft Deletes assumes that `NULL` indicates an active (undeleted) record. However, this assumption conflicts with database partitioning requirements in databases like MySQL. In MySQL, all columns used in the partitioning expression must be part of every unique key on the table, including the primary key. Since primary key columns cannot be nullable, it is impossible to use a nullable `deleted_at` column as a partition key.

With this package, developers can define a non-null "undeleted" value (e.g., `'9999-12-31 23:59:59'`). This allows `deleted_at` to be defined as `NOT NULL`, making it eligible for use in partitioning keys while retaining all native Soft Deletes functionality.

## Requirements
- PHP 8.2 or higher
- Laravel 10.x, 11.x, or 12.x

## Installation

You can install the package via composer:

```bash
composer require to-mo-ki/laravel-soft-deletes-value
```

## Usage

Replace Laravel's default `Illuminate\Database\Eloquent\SoftDeletes` trait with `Tomoki\SoftDeletesValue\SoftDeletes` in your Eloquent models.

Then, define the `$undeletedValue` property indicating what value represents an active, non-deleted state.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Tomoki\SoftDeletesValue\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    /**
     * The value that indicates the model is not soft deleted.
     *
     * @var string
     */
    protected $undeletedValue = '9999-12-31 23:59:59';
}
```

### Optional: Include `NULL` in `onlyTrashed()`

By default, this package uses only value comparisons:

- active: `deleted_at = $undeletedValue`
- trashed: `deleted_at != $undeletedValue`

If you need `onlyTrashed()` to also include records where `deleted_at` is `NULL` (for legacy nullable schemas), set `$treatNullAsDeleted = true`.

```php
class Post extends Model
{
    use SoftDeletes;

    protected $undeletedValue = '9999-12-31 23:59:59';

    protected bool $treatNullAsDeleted = true;
}
```

Note: if `$undeletedValue` is `null`, Laravel's query builder maps `where(..., '!=', null)` to `whereNotNull(...)`.
Also note that `$treatNullAsDeleted` only affects `onlyTrashed()`. It does not change the default `withoutTrashed()` filter or the `trashed()` helper behavior.
This option is intended for legacy nullable data migration. The recommended setup is a non-nullable `deleted_at` column with a fixed undeleted value.

### Behavior Matrix

Assuming `$undeletedValue = '9999-12-31 23:59:59'` and a legacy row where `deleted_at = NULL`:

| Setting | `withoutTrashed()` / default scope | `onlyTrashed()` | `$model->trashed()` (`deleted_at = NULL`) |
| --- | --- | --- | --- |
| `$treatNullAsDeleted = false` (default) | Excludes `NULL` rows | Excludes `NULL` rows | `false` |
| `$treatNullAsDeleted = true` | Excludes `NULL` rows | Includes `NULL` rows | `false` |

### Database Migration

Ensure your database migration uses `NOT NULL` with the default value matching the one defined in your model.

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->timestamp('deleted_at')->default('9999-12-31 23:59:59'); // NOT NULL by default in Laravel or specify ->nullable(false)
    $table->timestamps();
});
```

## Supported Methods

This package supports all the standard Eloquent Soft Delete methods seamlessly:

- `$model->delete()`
- `$model->restore()`
- `$model->forceDelete()`
- `$model->trashed()`
- `Model::withTrashed()`
- `Model::onlyTrashed()`
- `Model::withoutTrashed()`

## Quality Assurance

We maintain the highest code quality standards for this package:

- **Style:** Laravel Pint (standard Laravel preset)
- **Static Analysis:** PHPStan Level 9 (via Larastan)
- **Code Coverage:** 100% Line/Method/Class coverage
- **Mutation Testing:** 100% Mutation Score Index (MSI) (via Infection)

### Running QA Tools with Docker

```bash
# 1. Format code (Pint)
docker run --rm -v $(pwd):/app laravel-pkg-dev composer pint

# 2. Static Analysis (PHPStan Level 9)
docker run --rm -v $(pwd):/app laravel-pkg-dev composer phpstan

# 3. Unit Tests & Code Coverage (Terminal output)
docker run --rm -v $(pwd):/app laravel-pkg-dev composer coverage-text

# 4. Mutation Testing (Infection)
docker run --rm -v $(pwd):/app laravel-pkg-dev composer infection
```

## Testing

```bash
# Run tests
composer test

# Run tests with coverage summary
composer coverage-text
```

### Testing with Docker (Code Coverage)

If you want to run tests with code coverage (PCOV) using Docker:

1.  **Build the development image**:
    ```bash
    docker build -t laravel-pkg-dev -f requirements-dev.dockerfile .
    ```

2.  **Run tests with coverage (Terminal output)**:
    ```bash
    docker run --rm -v $(pwd):/app laravel-pkg-dev composer coverage-text
    ```

3.  **Generate HTML coverage report**:
    ```bash
    docker run --rm -v $(pwd):/app laravel-pkg-dev composer coverage-html
    ```
    After running this, you can open `coverage-report/index.html` in your browser.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
