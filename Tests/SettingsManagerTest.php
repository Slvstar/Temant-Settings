<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Tests;

use DateTimeImmutable;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Temant\SettingsManager\Entity\SettingEntity;
use Temant\SettingsManager\Enum\SettingType;
use Temant\SettingsManager\Enum\UpdateType;
use Temant\SettingsManager\Exception\SettingAlreadyExistsException;
use Temant\SettingsManager\Exception\SettingNotFoundException;
use Temant\SettingsManager\Exception\SettingTypeMismatchException;
use Temant\SettingsManager\SettingsManager;

class SettingsManagerTest extends TestCase
{
    private SettingsManager $settingsManager;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(
            [dirname(__DIR__) . '/Src'],
            true,
        );
        $config->enableNativeLazyObjects(true);

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        $this->entityManager = new EntityManager($connection, $config);
        $this->settingsManager = new SettingsManager($this->entityManager);
    }

    // -------------------------------------------------------------------------
    //  Basic CRUD
    // -------------------------------------------------------------------------

    public function testSetSetting(): void
    {
        $this->settingsManager->set('testSetSetting', 'testSetSetting', SettingType::STRING);
        $setting = $this->settingsManager->get('testSetSetting');

        $this->assertInstanceOf(SettingEntity::class, $setting);
        $this->assertEquals('testSetSetting', $setting->getValue());
    }

    public function testSetWithAutoType(): void
    {
        $this->settingsManager->set('str', 'stringValue', SettingType::AUTO);
        $this->assertEquals(SettingType::STRING, $this->settingsManager->get('str')->getType());

        $this->settingsManager->set('int', 123, SettingType::AUTO);
        $this->assertEquals(SettingType::INTEGER, $this->settingsManager->get('int')->getType());

        $this->settingsManager->set('bool', true, SettingType::AUTO);
        $this->assertEquals(SettingType::BOOLEAN, $this->settingsManager->get('bool')->getType());

        $this->settingsManager->set('float', 1.23, SettingType::AUTO);
        $this->assertEquals(SettingType::FLOAT, $this->settingsManager->get('float')->getType());

        $this->settingsManager->set('json', '{"key":"value"}', SettingType::AUTO);
        $this->assertEquals(SettingType::JSON, $this->settingsManager->get('json')->getType());
    }

    public function testSetWithArrayType(): void
    {
        $this->settingsManager->set('tags', ['php', 'doctrine']);
        $setting = $this->settingsManager->get('tags');

        $this->assertNotNull($setting);
        $this->assertEquals(SettingType::ARRAY, $setting->getType());
        $this->assertEquals(['php', 'doctrine'], $setting->getValue());
    }

    public function testSetWithDatetimeType(): void
    {
        $date = new DateTimeImmutable('2026-06-15T12:00:00+00:00');
        $this->settingsManager->set('launch', $date);
        $setting = $this->settingsManager->get('launch');

        $this->assertNotNull($setting);
        $this->assertEquals(SettingType::DATETIME, $setting->getType());
        $this->assertInstanceOf(DateTimeImmutable::class, $setting->getValue());
        $this->assertEquals('2026-06-15', $setting->getValue()->format('Y-m-d'));
    }

    public function testSetWithDescriptionAndGroup(): void
    {
        $this->settingsManager->set(
            name: 'site_name',
            value: 'Acme',
            description: 'The site name',
            group: 'site',
        );

        $setting = $this->settingsManager->get('site_name');
        $this->assertEquals('The site name', $setting->getDescription());
        $this->assertEquals('site', $setting->getGroup());
    }

    public function testSetDuplicateSettingWithUpdateAllowed(): void
    {
        $this->settingsManager->set('dup', 'initial', SettingType::STRING);
        $this->settingsManager->set('dup', 'updated', SettingType::STRING);

        $this->assertEquals('updated', $this->settingsManager->get('dup')->getValue());
    }

    public function testSetDuplicateSettingWithoutUpdateAllowed(): void
    {
        $this->settingsManager->set('dup', 'value', SettingType::STRING);

        $this->expectException(SettingAlreadyExistsException::class);
        $this->settingsManager->set('dup', 'newValue', SettingType::STRING, false);
    }

    public function testUpdateSetting(): void
    {
        $this->settingsManager->set('upd', 'initial', SettingType::STRING);
        $this->settingsManager->update('upd', 'updated');

        $this->assertEquals('updated', $this->settingsManager->get('upd')->getValue());
    }

    public function testUpdateNonExistentSettingThrowsException(): void
    {
        $this->expectException(SettingNotFoundException::class);
        $this->settingsManager->update('nonexistent', 'value');
    }

    public function testUpdateSettingWithTypeOverride(): void
    {
        $this->settingsManager->set('typed', 'initial', SettingType::STRING);
        $this->settingsManager->update('typed', 123, UpdateType::OVERRIDE);

        $setting = $this->settingsManager->get('typed');
        $this->assertEquals(123, $setting->getValue());
        $this->assertEquals(SettingType::INTEGER, $setting->getType());
    }

    public function testUpdateSettingWithTypeKeepCurrent(): void
    {
        $this->settingsManager->set('typed', 'initial', SettingType::STRING);

        $this->expectException(SettingTypeMismatchException::class);
        $this->settingsManager->update('typed', 123, UpdateType::KEEP_CURRENT);
    }

    public function testRemoveSetting(): void
    {
        $this->settingsManager->set('del', 'value', SettingType::STRING);
        $this->settingsManager->remove('del');

        $this->assertNull($this->settingsManager->get('del'));
    }

    public function testRemoveNonExistentSettingThrowsException(): void
    {
        $this->expectException(SettingNotFoundException::class);
        $this->settingsManager->remove('nonexistent');
    }

    // -------------------------------------------------------------------------
    //  Convenience Methods
    // -------------------------------------------------------------------------

    public function testGetValue(): void
    {
        $this->settingsManager->set('val', 42, SettingType::INTEGER);

        $this->assertEquals(42, $this->settingsManager->getValue('val'));
        $this->assertNull($this->settingsManager->getValue('missing'));
    }

    public function testGetOrDefault(): void
    {
        $this->settingsManager->set('exists', 'hello', SettingType::STRING);

        $this->assertEquals('hello', $this->settingsManager->getOrDefault('exists', 'fallback'));
        $this->assertEquals('fallback', $this->settingsManager->getOrDefault('missing', 'fallback'));
        $this->assertNull($this->settingsManager->getOrDefault('missing'));
    }

    public function testExistsAndHas(): void
    {
        $this->settingsManager->set('key', 'value', SettingType::STRING);

        $this->assertTrue($this->settingsManager->exists('key'));
        $this->assertTrue($this->settingsManager->has('key'));
        $this->assertFalse($this->settingsManager->exists('missing'));
        $this->assertFalse($this->settingsManager->has('missing'));
    }

    public function testAllReturnsAllSettings(): void
    {
        $this->settingsManager->set('a', 'value1', SettingType::STRING);
        $this->settingsManager->set('b', 'value2', SettingType::STRING);

        $settings = $this->settingsManager->all();

        $this->assertCount(2, $settings);
        $this->assertInstanceOf(SettingEntity::class, $settings[0]);
    }

    // -------------------------------------------------------------------------
    //  Bulk Operations
    // -------------------------------------------------------------------------

    public function testSetMany(): void
    {
        $this->settingsManager->setMany([
            'site.name' => ['value' => 'Acme', 'type' => SettingType::STRING, 'group' => 'site'],
            'cache.ttl' => ['value' => 3600, 'description' => 'Cache lifetime in seconds'],
        ]);

        $this->assertEquals('Acme', $this->settingsManager->getValue('site.name'));
        $this->assertEquals(3600, $this->settingsManager->getValue('cache.ttl'));
        $this->assertEquals('site', $this->settingsManager->get('site.name')->getGroup());
        $this->assertEquals('Cache lifetime in seconds', $this->settingsManager->get('cache.ttl')->getDescription());
    }

    public function testRemoveMany(): void
    {
        $this->settingsManager->set('a', '1', SettingType::STRING);
        $this->settingsManager->set('b', '2', SettingType::STRING);
        $this->settingsManager->set('c', '3', SettingType::STRING);

        $this->settingsManager->removeMany(['a', 'b']);

        $this->assertFalse($this->settingsManager->exists('a'));
        $this->assertFalse($this->settingsManager->exists('b'));
        $this->assertTrue($this->settingsManager->exists('c'));
    }

    public function testClear(): void
    {
        $this->settingsManager->set('a', '1', SettingType::STRING);
        $this->settingsManager->set('b', '2', SettingType::STRING);

        $this->settingsManager->clear();

        $this->assertEquals(0, count($this->settingsManager));
    }

    // -------------------------------------------------------------------------
    //  Search & Filter
    // -------------------------------------------------------------------------

    public function testSearch(): void
    {
        $this->settingsManager->set('site.name', 'Acme', SettingType::STRING);
        $this->settingsManager->set('site.url', 'https://example.com', SettingType::STRING);
        $this->settingsManager->set('cache.ttl', '60', SettingType::STRING);

        $results = $this->settingsManager->search('site');

        $this->assertCount(2, $results);
    }

    public function testFindByGroup(): void
    {
        $this->settingsManager->set('a', '1', group: 'alpha');
        $this->settingsManager->set('b', '2', group: 'alpha');
        $this->settingsManager->set('c', '3', group: 'beta');

        $alpha = $this->settingsManager->findByGroup('alpha');
        $beta = $this->settingsManager->findByGroup('beta');

        $this->assertCount(2, $alpha);
        $this->assertCount(1, $beta);
    }

    // -------------------------------------------------------------------------
    //  Counting
    // -------------------------------------------------------------------------

    public function testCountable(): void
    {
        $this->assertEquals(0, count($this->settingsManager));

        $this->settingsManager->set('a', '1', SettingType::STRING);
        $this->settingsManager->set('b', '2', SettingType::STRING);

        $this->assertEquals(2, count($this->settingsManager));
    }

    // -------------------------------------------------------------------------
    //  Import / Export
    // -------------------------------------------------------------------------

    public function testExportAndImport(): void
    {
        $this->settingsManager->set('key1', 'val1', SettingType::STRING);
        $this->settingsManager->set('key2', 42, SettingType::INTEGER);

        $exported = $this->settingsManager->export();

        $this->assertCount(2, $exported);
        $this->assertEquals('key1', $exported[0]['name']);

        // Export to JSON and re-import
        $json = $this->settingsManager->exportJson();
        $this->assertJson($json);

        $this->settingsManager->clear();
        $this->assertEquals(0, count($this->settingsManager));

        $this->settingsManager->importJson($json);
        $this->assertEquals(2, count($this->settingsManager));
    }

    // -------------------------------------------------------------------------
    //  Type Detection & Validation
    // -------------------------------------------------------------------------

    public function testDetectTypeWithValidValues(): void
    {
        $detect = fn(mixed $val) => $this->invokeMethod($this->settingsManager, 'detectType', [$val]);

        $this->assertEquals(SettingType::STRING, $detect('test'));
        $this->assertEquals(SettingType::INTEGER, $detect(123));
        $this->assertEquals(SettingType::BOOLEAN, $detect(true));
        $this->assertEquals(SettingType::FLOAT, $detect(1.23));
        $this->assertEquals(SettingType::JSON, $detect('{"key":"value"}'));
        $this->assertEquals(SettingType::ARRAY, $detect(['a', 'b']));
        $this->assertEquals(SettingType::DATETIME, $detect(new DateTimeImmutable()));
    }

    public function testDetectTypeBoolBeforeInt(): void
    {
        // Booleans must be detected before integers (true/false are technically int-like)
        $detect = fn(mixed $val) => $this->invokeMethod($this->settingsManager, 'detectType', [$val]);

        $this->assertEquals(SettingType::BOOLEAN, $detect(false));
        $this->assertEquals(SettingType::BOOLEAN, $detect(true));
    }

    public function testDetectTypeWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->invokeMethod($this->settingsManager, 'detectType', [new \stdClass()]);
    }

    #[TestWith([SettingType::STRING, 'test'])]
    #[TestWith([SettingType::INTEGER, 123])]
    #[TestWith([SettingType::BOOLEAN, true])]
    #[TestWith([SettingType::FLOAT, 1.12])]
    #[TestWith([SettingType::JSON, '{"key":"value"}'])]
    #[TestWith([SettingType::AUTO, 'test_auto'])]
    public function testValidateTypeWithValidTypes(SettingType $type, mixed $value): void
    {
        $this->expectNotToPerformAssertions();
        $this->invokeMethod($this->settingsManager, 'validateType', [$type, $value]);
    }

    // -------------------------------------------------------------------------
    //  Defaults
    // -------------------------------------------------------------------------

    public function testInitializeDefaults(): void
    {
        $defaults = [
            'site_name'        => ['value' => 'My Website', 'type' => SettingType::STRING],
            'maintenance_mode' => ['value' => false, 'type' => SettingType::BOOLEAN],
            'max_users'        => ['value' => 1000, 'type' => SettingType::INTEGER],
        ];

        $manager = new SettingsManager($this->entityManager, 'settings', $defaults);

        $this->assertEquals('My Website', $manager->getValue('site_name'));
        $this->assertFalse($manager->getValue('maintenance_mode'));
        $this->assertEquals(1000, $manager->getValue('max_users'));
    }

    public function testInitializeDefaultsDoesNotOverwriteExistingSettings(): void
    {
        $this->settingsManager->set('site_name', 'Existing Website', SettingType::STRING);

        $defaults = [
            'site_name'        => ['value' => 'My Website', 'type' => SettingType::STRING],
            'maintenance_mode' => ['value' => false, 'type' => SettingType::BOOLEAN],
        ];

        $manager = new SettingsManager($this->entityManager, 'settings', $defaults);

        $this->assertEquals('Existing Website', $manager->getValue('site_name'));
        $this->assertFalse($manager->getValue('maintenance_mode'));
    }

    public function testDefaultsWithGroupAndDescription(): void
    {
        $defaults = [
            'theme' => [
                'value' => 'dark',
                'type' => SettingType::STRING,
                'description' => 'UI color theme',
                'group' => 'ui',
            ],
        ];

        $manager = new SettingsManager($this->entityManager, 'settings', $defaults);
        $setting = $manager->get('theme');

        $this->assertEquals('dark', $setting->getValue());
        $this->assertEquals('UI color theme', $setting->getDescription());
        $this->assertEquals('ui', $setting->getGroup());
    }

    public function testCanPassCustomTableName(): void
    {
        $manager = new SettingsManager($this->entityManager, 'custom_settings');
        $this->assertEquals('custom_settings', $manager->tableName);
    }

    public function testDefaultSettingsWithAutoType(): void
    {
        $manager = new SettingsManager($this->entityManager, 'very_custom_table', [
            'theme' => ['value' => 'dark', 'type' => SettingType::AUTO],
            'age'   => ['value' => 15],
        ]);

        $this->assertEquals('dark', $manager->getValue('theme'));
        $this->assertIsString($manager->getValue('theme'));

        $this->assertEquals(15, $manager->getValue('age'));
        $this->assertIsInt($manager->getValue('age'));
    }

    // -------------------------------------------------------------------------
    //  Cache
    // -------------------------------------------------------------------------

    public function testCacheIsUsed(): void
    {
        $this->settingsManager->set('cached', 'value', SettingType::STRING);

        // Second get should hit cache
        $a = $this->settingsManager->get('cached');
        $b = $this->settingsManager->get('cached');

        $this->assertSame($a, $b);
    }

    public function testClearCacheForcesDatabaseRead(): void
    {
        $this->settingsManager->set('cached', 'value', SettingType::STRING);
        $this->settingsManager->get('cached'); // populate cache

        $this->settingsManager->clearCache();

        // After clearing, the entity should still be retrievable from DB
        $this->assertEquals('value', $this->settingsManager->getValue('cached'));
    }

    // -------------------------------------------------------------------------
    //  Fluent Interface
    // -------------------------------------------------------------------------

    public function testFluentChaining(): void
    {
        $result = $this->settingsManager
            ->set('a', 'val1')
            ->set('b', 'val2')
            ->remove('a');

        $this->assertInstanceOf(SettingsManager::class, $result);
    }

    // -------------------------------------------------------------------------
    //  Helper
    // -------------------------------------------------------------------------

    private function invokeMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
