<?php declare(strict_types=1);

namespace Temant\SettingsManager\Tests\Utils;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\TestCase;
use stdClass;
use Temant\SettingsManager\Utils\SettingsImporter;
use Temant\SettingsManager\Utils\SettingsExporter;
use Temant\SettingsManager\SettingsManager;
use Temant\SettingsManager\Entity\SettingEntity;
use Temant\SettingsManager\Enum\SettingType;
use Temant\SettingsManager\Exception\SettingsImportExportException;

class SettingsImportExportTest extends TestCase
{
    private SettingsManager $settingsManager;

    protected function setUp(): void
    {
        // Configure an in-memory SQLite database for testing
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__], false);
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        $entityManager = new EntityManager($connection, $config);

        // Initialize SettingsManager
        $this->settingsManager = new SettingsManager($entityManager);

        // Set up initial settings
        $this->settingsManager->set('testKey', 'testValue', SettingType::STRING);
    }

    public function testExportToArray(): void
    {
        // Test the exportToArray method
        $result = SettingsExporter::toArray($this->settingsManager);

        // Check that the exported array contains the expected values
        $this->assertCount(1, $result);
        $this->assertEquals('testKey', $result[0]['name']);
        $this->assertEquals('testValue', $result[0]['value']);
        $this->assertEquals('string', $result[0]['type']);
    }

    public function testExportToJson(): void
    {
        // Test the exportToJson method
        $jsonData = SettingsExporter::toJson($this->settingsManager);
        $decoded = json_decode($jsonData, true);

        // Check that the exported JSON contains the expected values
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertEquals('testKey', $decoded[0]['name']);
        $this->assertEquals('testValue', $decoded[0]['value']);
        $this->assertEquals('string', $decoded[0]['type']);
    }

    public function testImportFromArrayThrowsException(): void
    {
        // Create invalid data to force an exception
        $invalidSettingsData = [
            [
                'name' => 'site_name',
                'type' => 'invalid_type',  // Invalid type to trigger an exception
                'value' => 'My Website'
            ]
        ];

        // Expect the custom SettingsImportExportException to be thrown
        $this->expectException(SettingsImportExportException::class);

        // Run the fromArray method with invalid data, expecting it to fail
        SettingsImporter::fromArray($this->settingsManager, $invalidSettingsData);
    }


    public function testImportFromArray(): void
    {
        // Define the new settings to import
        $settingsData = [
            [
                'name' => 'site_name',
                'type' => 'string',
                'value' => 'My Website',
            ]
        ];

        // Test the importFromArray method
        SettingsImporter::fromArray($this->settingsManager, $settingsData);

        // Check that the setting was imported correctly
        $importedSetting = $this->settingsManager->get('site_name');
        $this->assertNotNull($importedSetting);
        $this->assertEquals('My Website', $importedSetting->getValue());
    }

    public function testImportFromJson(): void
    {
        // Define the JSON data to import
        $jsonData = json_encode([
            [
                'name' => 'site_name',
                'type' => 'string',
                'value' => 'My Website',
            ]
        ]);

        // Test the importFromJson method
        SettingsImporter::fromJson($this->settingsManager, $jsonData);

        // Check that the setting was imported correctly
        $importedSetting = $this->settingsManager->get('site_name');
        $this->assertNotNull($importedSetting);
        $this->assertEquals('My Website', $importedSetting->getValue());
    }

    public function testImportFromJsonThrowsException(): void
    {
        // Test exception when invalid JSON is provided
        $this->expectException(SettingsImportExportException::class);

        SettingsImporter::fromJson($this->settingsManager, 'invalid json');
    }
}
