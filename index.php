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
$settingsManager = new SettingsManager($entityManager);

// Add a new setting
try {
    $settingsManager->add('new_setting', SettingType::STRING, 'Initial Value');
    echo "Setting 'new_setting' added successfully.\n";
} catch (\RuntimeException $e) {
    echo "Failed to add 'new_setting': " . $e->getMessage() . "\n";
}

// Set or update a setting
$settingsManager->set('site_name', SettingType::STRING, 'My Custom Website');
echo "Setting 'site_name' set or updated successfully.\n";

// Update an existing setting
try {
    $settingsManager->update('site_name', 'My Updated Website');
    echo "Setting 'site_name' updated successfully.\n";
} catch (\RuntimeException $e) {
    echo "Failed to update 'site_name': " . $e->getMessage() . "\n";
}

// Get a setting value
$siteName = $settingsManager->get('site_name');
echo "The value of 'site_name' is: " . $siteName . "\n";

// Check if a setting exists
if ($settingsManager->exists('site_name')) {
    echo "'site_name' exists.\n";
} else {
    echo "'site_name' does not exist.\n";
}

// Remove a setting
$settingsManager->remove('new_setting');
echo "Setting 'new_setting' removed successfully.\n";

// Retrieve all settings
$allSettings = $settingsManager->all();
foreach ($allSettings as $setting) {
    echo "Setting: " . $setting->getName() . " => " . $setting->getValue() . "\n";
}

// Additional error handling and logic
try {
    // Attempt to add a setting that already exists to demonstrate error handling
    $settingsManager->add('site_name', SettingType::STRING, 'Duplicate Value');
} catch (\RuntimeException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}