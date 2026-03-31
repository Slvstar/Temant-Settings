
# Temant Settings Manager

![Build Status](https://github.com/Slvstar/Temant-Settings/actions/workflows/ci.yml/badge.svg)
![Coverage Status](https://codecov.io/gh/Slvstar/Temant-Settings/branch/main/graph/badge.svg)
![License](https://img.shields.io/github/license/Slvstar/Temant-Settings)
![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)
![PHP](https://img.shields.io/badge/PHP-8.5%2B-8892BF)

A modern, type-safe settings management library for PHP applications powered by Doctrine ORM.
Persist key-value settings to any database with automatic type detection, in-memory caching, group organisation, bulk operations, and import/export.

## Features

- **8 data types** — `string`, `integer`, `boolean`, `float`, `json`, `array`, `datetime`, and `auto` (auto-detect)
- **In-memory cache** — subsequent reads for the same key skip the database
- **Groups & descriptions** — organise settings logically and document them inline
- **Bulk operations** — `setMany()`, `removeMany()`, `clear()`
- **Search & filter** — substring search and group-based filtering
- **Import / Export** — JSON and array formats, round-trip safe
- **Fluent interface** — chain calls: `$manager->set(...)->set(...)->remove(...)`
- **Countable** — `count($manager)` returns the total number of settings
- **Doctrine ORM 3** — works with MySQL, PostgreSQL, and SQLite
- **PHPStan max level** — fully statically analysed
- **65 tests, 141 assertions** — comprehensive test suite

## Installation

```bash
composer require temant/settings-manager
```

Requires **PHP 8.5+** and **Doctrine ORM 3**.

## Quick Start

```php
use Temant\SettingsManager\SettingsManager;
use Temant\SettingsManager\Enum\SettingType;

// Create a manager with your Doctrine EntityManager
$manager = new SettingsManager($entityManager);

// Set values — type is auto-detected
$manager->set('site.name', 'Acme Corp');
$manager->set('cache.ttl', 3600);
$manager->set('debug', false);

// Get typed values back
$manager->getValue('site.name');   // 'Acme Corp' (string)
$manager->getValue('cache.ttl');   // 3600 (int)
$manager->getValue('debug');       // false (bool)

// Safe defaults for missing keys
$manager->getOrDefault('ui.theme', 'dark'); // 'dark'
```

## Usage Guide

### Setting Values

```php
use Temant\SettingsManager\Enum\SettingType;

// Auto-detect type (default)
$manager->set('key', 'value');
$manager->set('count', 42);
$manager->set('enabled', true);
$manager->set('rate', 0.75);
$manager->set('config', '{"nested": true}');    // detected as JSON
$manager->set('tags', ['php', 'doctrine']);      // stored as ARRAY
$manager->set('launch', new DateTimeImmutable()); // stored as DATETIME

// Explicit type
$manager->set('port', '8080', SettingType::STRING); // force string, not int

// With metadata
$manager->set(
    name: 'smtp.host',
    value: 'mail.example.com',
    description: 'SMTP server hostname',
    group: 'email',
);

// Prevent accidental overwrites
$manager->set('api.key', 'secret', allowUpdate: false);
// throws SettingAlreadyExistsException if 'api.key' exists
```

### Retrieving Values

```php
// Full entity (with metadata, timestamps, etc.)
$entity = $manager->get('site.name');
$entity->getValue();       // typed value
$entity->getRawValue();    // raw string from DB
$entity->getType();        // SettingType::STRING
$entity->getDescription(); // ?string
$entity->getGroup();       // ?string
$entity->getCreatedAt();   // DateTimeImmutable
$entity->getUpdatedAt();   // ?DateTimeImmutable

// Shorthand — typed value directly
$manager->getValue('site.name');              // 'Acme Corp'

// With fallback
$manager->getOrDefault('missing.key', 'default'); // 'default'

// Existence check
$manager->exists('site.name'); // true
$manager->has('site.name');    // alias
```

### Updating Values

```php
use Temant\SettingsManager\Enum\UpdateType;

// Update value and auto-detect new type
$manager->update('cache.ttl', 7200);

// Update value but keep the original type (validates compatibility)
$manager->update('cache.ttl', 'not-an-int', UpdateType::KEEP_CURRENT);
// throws SettingTypeMismatchException
```

### Removing Values

```php
$manager->remove('old.setting');

// Remove multiple
$manager->removeMany(['key1', 'key2', 'key3']);

// Remove everything
$manager->clear();
```

### Bulk Operations

```php
$manager->setMany([
    'site.name'  => ['value' => 'Acme', 'type' => SettingType::STRING, 'group' => 'site'],
    'site.url'   => ['value' => 'https://acme.dev', 'group' => 'site'],
    'cache.ttl'  => ['value' => 3600, 'description' => 'Cache lifetime in seconds'],
    'debug.mode' => ['value' => false],
]);
```

### Search & Filter

```php
// Substring search (case-insensitive)
$results = $manager->search('site');    // all settings with 'site' in the name

// Group filtering
$emailSettings = $manager->findByGroup('email');
```

### Counting

```php
$total = count($manager); // implements Countable
```

### Import & Export

```php
// Export all settings to array
$data = $manager->export();

// Export to JSON
$json = $manager->exportJson();

// Import from array
$manager->import($data);

// Import from JSON
$manager->importJson($json);

// Static utility classes also available
use Temant\SettingsManager\Utils\SettingsExporter;
use Temant\SettingsManager\Utils\SettingsImporter;

$json = SettingsExporter::toJson($manager);
SettingsImporter::fromJson($manager, $json);
```

### Default Settings

Seed settings on first run — existing values are never overwritten:

```php
$manager = new SettingsManager($entityManager, 'settings', [
    'site.name' => [
        'value' => 'My App',
        'type' => SettingType::STRING,
        'description' => 'Application name',
        'group' => 'site',
    ],
    'cache.ttl' => [
        'value' => 3600,
        // type auto-detected as INTEGER
    ],
    'debug' => [
        'value' => false,
    ],
]);
```

### Custom Table Name

```php
$manager = new SettingsManager($entityManager, 'app_config');
```

### Cache Management

The manager caches entities in memory. If you modify the settings table externally:

```php
$manager->clearCache();
```

## Data Types

| SettingType | PHP Type            | Storage Format      | Example                |
|-------------|---------------------|---------------------|------------------------|
| `STRING`    | `string`            | As-is               | `'hello'`              |
| `INTEGER`   | `int`               | String cast          | `42`                   |
| `BOOLEAN`   | `bool`              | `'true'` / `'false'` | `true`                |
| `FLOAT`     | `float`             | String cast          | `3.14`                 |
| `JSON`      | `array` (assoc)     | JSON string          | `'{"key":"val"}'`      |
| `ARRAY`     | `array`             | JSON encoded         | `['a', 'b']`           |
| `DATETIME`  | `DateTimeImmutable` | ISO 8601             | `new DateTimeImmutable` |
| `AUTO`      | *(detected)*        | *(varies)*           | *(any of the above)*   |

## Exceptions

| Exception                           | When                                         |
|-------------------------------------|----------------------------------------------|
| `SettingAlreadyExistsException`     | `set()` with `allowUpdate: false` on existing key |
| `SettingNotFoundException`          | `update()` / `remove()` on missing key        |
| `SettingTypeMismatchException`      | Value incompatible with expected type         |
| `SettingsImportExportException`     | Import/export failure (bad JSON, missing keys) |
| `SettingsTableInitializationException` | Database table creation failure             |

## Running Tests

```bash
composer test
```

## Static Analysis

```bash
composer phpstan
```

## Run Both

```bash
composer check-all
```

## Contributing

Contributions are welcome! Feel free to submit issues or pull requests.

## License

[MIT](LICENSE)
