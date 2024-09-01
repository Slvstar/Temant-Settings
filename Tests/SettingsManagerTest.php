<?php declare(strict_types=1);

namespace Temant\SettingsManager\Tests;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Temant\SettingsManager\Entity\Setting;
use Temant\SettingsManager\Enum\SettingType;
use Temant\SettingsManager\Exception\SettingAlreadyExistsException;
use Temant\SettingsManager\Exception\SettingNotFoundException;
use Temant\SettingsManager\SettingsManager;
use Temant\SettingsManager\Initialization\SettingsTableInitializer;

class SettingsManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private SettingsManager $settingsManager;

    protected function setUp(): void
    {
        // Configure an in-memory SQLite database for testing

        $config = ORMSetup::createAttributeMetadataConfiguration(
            [__DIR__],
            false
        );

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . '/db.sqlite',
        ], $config);


        // $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/../src/Entity'], true, null, null, false);
        $this->entityManager = new EntityManager($connection, $config);

        // Initialize the schema
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // Initialize SettingsManager
        $this->settingsManager = new SettingsManager($this->entityManager);
    }

    public function testAddSetting(): void
    {
        $this->settingsManager->add('test_setting', SettingType::STRING, 'test_value');
        $setting = $this->settingsManager->get('test_setting');

        $this->assertInstanceOf(Setting::class, $setting);
        $this->assertEquals('test_value', $setting->getValue());
    }

    public function testAddDuplicateSettingThrowsException(): void
    {
        $this->settingsManager->add('test_setting', SettingType::STRING, 'test_value');

        $this->expectException(SettingAlreadyExistsException::class);
        $this->settingsManager->add('test_setting', SettingType::STRING, 'test_value');
    }

    public function testUpdateSetting(): void
    {
        $this->settingsManager->add('test_setting', SettingType::STRING, 'initial_value');
        $this->settingsManager->update('test_setting', 'updated_value');
        $setting = $this->settingsManager->get('test_setting');

        $this->assertEquals('updated_value', $setting->getValue());
    }

    public function testUpdateNonExistentSettingThrowsException(): void
    {
        $this->expectException(SettingNotFoundException::class);
        $this->settingsManager->update('non_existent_setting', 'value');
    }

    public function testRemoveSetting(): void
    {
        $this->settingsManager->add('test_setting', SettingType::STRING, 'value');
        $this->settingsManager->remove('test_setting');

        $setting = $this->settingsManager->get('test_setting');
        $this->assertNull($setting);
    }

    public function testRemoveNonExistentSettingThrowsException(): void
    {
        $this->expectException(SettingNotFoundException::class);
        $this->settingsManager->remove('non_existent_setting');
    }
}
