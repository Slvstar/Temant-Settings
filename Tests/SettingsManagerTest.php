<?php declare(strict_types=1);

namespace Temant\SettingsManager\Tests;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Temant\SettingsManager\Entity\Setting;
use Temant\SettingsManager\Enum\SettingType;
use Temant\SettingsManager\Exception\SettingAlreadyExistsException;
use Temant\SettingsManager\Exception\SettingNotFoundException;
use Temant\SettingsManager\SettingsManager;

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
            'memory' => true,
        ], $config);


        // $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/../src/Entity'], true, null, null, false);
        $this->entityManager = new EntityManager($connection, $config);

        // Initialize SettingsManager
        $this->settingsManager = new SettingsManager($this->entityManager);
    }

    public function testAddSetting(): void
    {
        $this->settingsManager->add('testAddSetting', SettingType::STRING, 'testAddSetting');
        $setting = $this->settingsManager->get('testAddSetting');

        $this->assertInstanceOf(Setting::class, $setting);
        $this->assertEquals('testAddSetting', $setting->getValue());

        $this->settingsManager->remove('testAddSetting');
    }

    public function testAddDuplicateSettingThrowsException(): void
    {
        if (!$this->settingsManager->exists('testAddDuplicateSettingThrowsException')) {
            $this->settingsManager->add('testAddDuplicateSettingThrowsException', SettingType::STRING, 'testAddDuplicateSettingThrowsException');
        }

        $this->expectException(SettingAlreadyExistsException::class);
        $this->settingsManager->add('testAddDuplicateSettingThrowsException', SettingType::STRING, 'testAddDuplicateSettingThrowsException');
    }

    public function testUpdateSetting(): void
    {
        $this->settingsManager->add('testUpdateSetting', SettingType::STRING, 'testUpdateSetting');
        $this->settingsManager->update('testUpdateSetting', 'updatedValue');
        $setting = $this->settingsManager->get('testUpdateSetting');

        $this->assertEquals('updatedValue', $setting->getValue());

        $this->settingsManager->remove('testUpdateSetting');
    }

    public function testUpdateNonExistentSettingThrowsException(): void
    {
        $this->expectException(SettingNotFoundException::class);
        $this->settingsManager->update('testUpdateNonExistentSettingThrowsException', 'value');
    }

    public function testRemoveSetting(): void
    {
        $this->settingsManager->add('testRemoveSetting', SettingType::STRING, 'testRemoveSetting');
        $this->settingsManager->remove('testRemoveSetting');

        $setting = $this->settingsManager->get('testRemoveSetting');
        $this->assertNull($setting);
    }

    public function testRemoveNonExistentSettingThrowsException(): void
    {
        $this->expectException(SettingNotFoundException::class);
        $this->settingsManager->remove('testRemoveNonExistentSettingThrowsException');
    }
}
