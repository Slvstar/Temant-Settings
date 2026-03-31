<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Tests\Utils;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\TestCase;
use Temant\SettingsManager\Enum\SettingType;
use Temant\SettingsManager\Exception\SettingsImportExportException;
use Temant\SettingsManager\SettingsManager;
use Temant\SettingsManager\Utils\SettingsExporter;
use Temant\SettingsManager\Utils\SettingsImporter;

class SettingsImportExportTest extends TestCase
{
    private SettingsManager $settingsManager;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            [dirname(__DIR__, 2) . '/Src'],
            true,
        );
        $config->enableNativeLazyObjects(true);

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

    public function testExportIncludesDescriptionAndGroup(): void
    {
        $this->settingsManager->set(
            name: 'grouped',
            value: 'val',
            type: SettingType::STRING,
            description: 'A desc',
            group: 'grp',
        );

        $result = SettingsExporter::toArray($this->settingsManager);
        $grouped = array_values(array_filter($result, fn($r) => $r['name'] === 'grouped'));

        $this->assertCount(1, $grouped);
        $this->assertEquals('A desc', $grouped[0]['description']);
        $this->assertEquals('grp', $grouped[0]['group']);
    }

    public function testImportFromArray(): void
    {
        $settingsData = [
            ['name' => 'site_name', 'type' => 'string', 'value' => 'My Website'],
        ];

        SettingsImporter::fromArray($this->settingsManager, $settingsData);

        $imported = $this->settingsManager->get('site_name');
        $this->assertNotNull($imported);
        $this->assertEquals('My Website', $imported->getValue());
    }

    public function testImportFromArrayThrowsOnInvalidType(): void
    {
        $this->expectException(SettingsImportExportException::class);

        SettingsImporter::fromArray($this->settingsManager, [
            ['name' => 'site_name', 'type' => 'invalid_type', 'value' => 'My Website'],
        ]);
    }

    public function testImportFromArrayThrowsOnMissingKeys(): void
    {
        $this->expectException(SettingsImportExportException::class);
        $this->expectExceptionMessage('missing required keys');

        SettingsImporter::fromArray($this->settingsManager, [
            ['name' => 'site_name'], // missing value and type
        ]);
    }

    public function testImportFromJson(): void
    {
        $jsonData = json_encode([
            ['name' => 'site_name', 'type' => 'string', 'value' => 'My Website'],
        ]);

        SettingsImporter::fromJson($this->settingsManager, $jsonData);

        $imported = $this->settingsManager->get('site_name');
        $this->assertNotNull($imported);
        $this->assertEquals('My Website', $imported->getValue());
    }

    public function testImportFromJsonThrowsOnMalformedJson(): void
    {
        $this->expectException(SettingsImportExportException::class);

        SettingsImporter::fromJson($this->settingsManager, 'invalid json');
    }

    public function testImportFromJsonNonArrayThrowsException(): void
    {
        $this->expectException(SettingsImportExportException::class);
        $this->expectExceptionMessage('Invalid JSON format, array expected.');

        SettingsImporter::fromJson($this->settingsManager, '"This is a string, not an array"');
    }

    public function testImportWithDescriptionAndGroup(): void
    {
        $data = [
            [
                'name' => 'themed',
                'value' => 'dark',
                'type' => 'string',
                'description' => 'Color theme',
                'group' => 'ui',
            ],
        ];

        SettingsImporter::fromArray($this->settingsManager, $data);

        $setting = $this->settingsManager->get('themed');
        $this->assertEquals('Color theme', $setting->getDescription());
        $this->assertEquals('ui', $setting->getGroup());
    }
}
