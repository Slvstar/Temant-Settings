<?php declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Temant\SettingsManager\Enum\SettingType;
use Temant\SettingsManager\Enum\UpdateType;
use Temant\SettingsManager\Exception\SettingAlreadyExistsException;
use Temant\SettingsManager\Exception\SettingNotFoundException;
use Temant\SettingsManager\SettingsManager;
use Temant\SettingsManager\Utils\SettingsExporter;

require_once __DIR__ . "/vendor/autoload.php";

// Configure Doctrine ORM with your database settings
$config = ORMSetup::createAttributeMetadataConfiguration(
    [__DIR__ . "/src/Entity"],
    false
);

$connection = DriverManager::getConnection([
    'driver' => 'pdo_mysql',
    'host' => 'localhost',
    'user' => 'intradb',
    'password' => 'Proto!728agt22Ws',
    'dbname' => 'intradb'
], $config);

$entityManager = new EntityManager($connection, $config);

// Initialize the SettingsManager
$settingsManager = new SettingsManager($entityManager, "settings");

// Add a new setting
try {
    $settingsManager->set('new_setting', 'Initial Value', SettingType::STRING);
    dump("Setting 'new_setting' added successfully.");
} catch (SettingAlreadyExistsException $e) {
    dump("Failed to add 'new_setting': " . $e->getMessage());
}

// Set or update a setting
$settingsManager->set('site_name', 11);
dump("Setting 'site_name' set or updated successfully.");

// Update an existing setting
try {
    $settingsManager->update('site_name', 'My Updated Website', UpdateType::OVERRIDE);
    dump("Setting 'site_name' updated successfully.");
} catch (SettingNotFoundException $e) {
    dump("Failed to update 'site_name': " . $e->getMessage() . "");
}

// Get a setting value
$siteName = $settingsManager->get('site_name');
dump("The value of 'site_name' is: " . ($siteName ? $siteName->getValue() : 'Not Found') . "");

// Check if a setting exists
if ($settingsManager->exists('site_name')) {
    dump("'site_name' exists.");
} else {
    dump("'site_name' does not exist.");
}

// Remove a setting
try {
    $settingsManager->remove('site_name');
    dump("Setting 'site_name' removed successfully.");
} catch (SettingNotFoundException $e) {
    dump("Failed to remove 'site_name': " . $e->getMessage() . "");
}

// Check if a setting exists
if ($settingsManager->exists('site_name')) {
    dump("'site_name' exists.");
} else {
    dump("'site_name' does not exist.");
}

// Retrieve all settings
$allSettings = $settingsManager->all();
foreach ($allSettings as $setting) {
    dump("Setting: " . $setting->getName() . " => " . $setting->getValue() . "");
}

// Additional error handling and logic
try {
    // Attempt to add a setting that already exists to demonstrate error handling
    $settingsManager->set('site_name', 'Duplicate Value', SettingType::STRING, false);
} catch (SettingAlreadyExistsException $e) {
    dump("Error: " . $e->getMessage() . "");
}

// Check a setting that may not exist
$adminEmail = $settingsManager->get('admin_email');
dump($adminEmail ? "Admin email: " . $adminEmail->getValue() : "Admin email setting not found.");

// Export settings to JSON
$jsonData = SettingsExporter::toJson($settingsManager);

file_put_contents(__DIR__ . "/test.json", $jsonData);
dd($jsonData);