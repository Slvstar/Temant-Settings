
# Temant Settings Manager

![Build Status](https://github.com/EmadAlmahdi/Temant-Settings/actions/workflows/ci.yml/badge.svg) 
![Coverage Status](https://codecov.io/gh/EmadAlmahdi/Temant-Settings/branch/main/graph/badge.svg)
![License](https://img.shields.io/github/license/EmadAlmahdi/Temant-Settings)
![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)

A flexible settings management system for PHP applications, providing support for multiple data types and seamless integration with Doctrine ORM. It offers importing, exporting, and automatic type detection for settings.

## Features

- **Multiple Data Types**: Supports `string`, `integer`, `boolean`, `float`, and `JSON` types.
- **Import/Export**: Easily import/export settings in JSON or array format.
- **Doctrine ORM Integration**: Manage settings with the power of Doctrine ORM.
- **Automatic Type Detection**: Automatically detects the data type of settings.
- **Highly Customizable**: Extend and modify functionality with ease.

## Installation

1. Install via Composer:
    ```bash
    composer require temant/settings-manager
    ```

2. Ensure you have Doctrine ORM installed and configured properly in your project.

## Usage

### Basic Setup

```php
use Temant\SettingsManager\SettingsManager;
use Temant\SettingsManager\Enum\SettingType;
use Doctrine\ORM\EntityManagerInterface;

// Create a SettingsManager instance with Doctrine EntityManager
$settingsManager = new SettingsManager($entityManager);

// Add or update a setting
$settingsManager->set('site_name', 'My Awesome Site', SettingType::STRING);

// Retrieve a setting
$siteName = $settingsManager->get('site_name')->getValue();
echo $siteName; // Outputs 'My Awesome Site'

// Remove a setting
$settingsManager->remove('site_name');
```

### Importing and Exporting Settings

You can easily import or export settings in either array or JSON format.

#### Export to JSON

```php
use Temant\SettingsManager\Utils\SettingsExporter;

$jsonData = SettingsExporter::toJson($settingsManager);
echo $jsonData;
```

#### Import from JSON

```php
use Temant\SettingsManager\Utils\SettingsImporter;

$jsonData = '{"site_name": {"name": "site_name", "value": "My Awesome Site", "type": "STRING"}}';
SettingsImporter::fromJson($settingsManager, $jsonData);
```

## Running Tests

Run the test suite using PHPUnit:

```bash
composer test
```

Perform static analysis with PHPStan:

```bash
composer phpstan
```

## Contributing

We welcome contributions! Feel free to submit issues or pull requests. For major changes, please open an issue to discuss what you'd like to change.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
