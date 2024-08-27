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

// Set a setting using the interface methods
$settingsManager->set('site_name', 'My Custom Website');

// Get the setting value
$siteName = $settingsManager->get('site_name');
echo $siteName . PHP_EOL; // Outputs: My Custom Website

// Check if a setting exists
if ($settingsManager->exists('site_name')) {
    echo 'Site name setting exists' . PHP_EOL;
}

// Remove a setting
$settingsManager->remove('site_name');