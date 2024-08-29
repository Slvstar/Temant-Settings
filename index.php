<?php declare(strict_types=1);
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Temant\SettingsManager\Enum\SettingType;
use Temant\SettingsManager\SettingsManager;

require_once __DIR__ . "/vendor/autoload.php";

$config = ORMSetup::createAttributeMetadataConfiguration(
    [__DIR__ . "/Src/Entity"],
    false
);

$config->setAutoGenerateProxyClasses(true);

$connection = DriverManager::getConnection([
    'driver' => 'pdo_mysql',
    'host' => 'localhost',
    'user' => 'intradb',
    'password' => 'Proto!728agt22Ws',
    'dbname' => 'intradb'
], $config);

$entityManager = new EntityManager($connection, $config);

// Assume $entityManager is an instance of EntityManagerInterface
$settingsManager = new SettingsManager($entityManager, "Hello");

// Add a new setting
try {
    $settingsManager->add('new_setting', SettingType::STRING, 'Initial Value');
    dump("Setting 'new_setting' added successfully.");
} catch (\RuntimeException $e) {
    dump("Failed to add 'new_setting': " . $e->getMessage());
}

// Set or update a setting
$settingsManager->set('site_name', SettingType::STRING, 'My Custom Website');
dump("Setting 'site_name' set or updated successfully.");

// Update an existing setting
try {
    $settingsManager->update('site_name', 'My Updated Website');
    dump("Setting 'site_name' updated successfully.");
} catch (\RuntimeException $e) {
    dump("Failed to update 'site_name': " . $e->getMessage());
}

// Get a setting value
$siteName = $settingsManager->get('site_name');
dump("The value of 'site_name' is: $siteName</br>");

// Check if a setting exists
if ($settingsManager->exists('site_name')) {
    dump("'site_name' exists.");
} else {
    dump("'site_name' does not exist.");
}

// Remove a setting
$settingsManager->remove('new_setting');
dump("Setting 'new_setting' removed successfully.");

// Retrieve all settings
$allSettings = $settingsManager->all();
foreach ($allSettings as $setting) {
    dump("Setting: " . $setting->getName() . " => " . $setting->getValue());
}

// Additional error handling and logic
try {
    // Attempt to add a setting that already exists to demonstrate error handling
    $settingsManager->add('site_name', SettingType::STRING, 'Duplicate Value');
} catch (\RuntimeException $e) {
    dump("Error: " . $e->getMessage());
}

dd($settingsManager->get('admin_email'));