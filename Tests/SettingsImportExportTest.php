<?php declare(strict_types=1);

namespace Temant\SettingsManager\Tests\Utils;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\TestCase;
use Temant\SettingsManager\Utils\SettingsImporter;
use Temant\SettingsManager\Utils\SettingsExporter;
use Temant\SettingsManager\SettingsManager;
use Temant\SettingsManager\Enum\SettingType;
use Temant\SettingsManager\Exception\SettingsImportExportException;

class SettingsImportExportTest extends TestCase
{
    private SettingsManager $settingsManager;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__], false);
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        $entityManager = new EntityManager($connection, $config);

        $this->settingsManager = new SettingsManager($entityManager);

        $this->settingsManager->set('testKey', 'testValue', SettingType::STRING);
    }

    public function testExportToArray(): void
    {
        $result = SettingsExporter::toArray($this->settingsManager);

        $this->assertCount(1, $result);
        $this->assertEquals('testKey', $result[0]['name']);
        $this->assertEquals('testValue', $result[0]['value']);
        $this->assertEquals('string', $result[0]['type']);
    }

    public function testExportToJson(): void
    {
        $jsonData = SettingsExporter::toJson($this->settingsManager);
        $decoded = json_decode($jsonData, true);

        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertEquals('testKey', $decoded[0]['name']);
        $this->assertEquals('testValue', $decoded[0]['value']);
        $this->assertEquals('string', $decoded[0]['type']);
    }

    public function testImportFromArrayThrowsException(): void
    {
        $invalidSettingsData = [
            [
                'name' => 'site_name',
                'type' => 'invalid_type', 
                'value' => 'My Website'
            ]
        ];

        $this->expectException(SettingsImportExportException::class);

        SettingsImporter::fromArray($this->settingsManager, $invalidSettingsData);
    }


    public function testImportFromArray(): void
    {
        $settingsData = [
            [
                'name' => 'site_name',
                'type' => 'string',
                'value' => 'My Website',
            ]
        ];

        SettingsImporter::fromArray($this->settingsManager, $settingsData);

        $importedSetting = $this->settingsManager->get('site_name');
        $this->assertNotNull($importedSetting);
        $this->assertEquals('My Website', $importedSetting->getValue());
    }

    public function testImportFromJson(): void
    {
        $jsonData = json_encode([
            [
                'name' => 'site_name',
                'type' => 'string',
                'value' => 'My Website',
            ]
        ]);

        SettingsImporter::fromJson($this->settingsManager, $jsonData);

        $importedSetting = $this->settingsManager->get('site_name');
        $this->assertNotNull($importedSetting);
        $this->assertEquals('My Website', $importedSetting->getValue());
    }

    public function testImportFromJsonThrowsException(): void
    {
        $this->expectException(SettingsImportExportException::class);

        SettingsImporter::fromJson($this->settingsManager, 'invalid json');
    }

    public function testImportFromJsonNonArrayThrowsException(): void
    {
        $nonArrayJson = '"This is a string, not an array"';

        $this->expectException(SettingsImportExportException::class);
        $this->expectExceptionMessage('Invalid JSON format, array expected.');

        SettingsImporter::fromJson($this->settingsManager, $nonArrayJson);
    }
}
