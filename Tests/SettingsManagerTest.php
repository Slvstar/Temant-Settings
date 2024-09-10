<?php declare(strict_types=1);

namespace Temant\SettingsManager\Tests;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;
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
    }

    public function testSetSetting(): void
    {
        $this->settingsManager->set('testSetSetting', 'testSetSetting', SettingType::STRING);
        $setting = $this->settingsManager->get('testSetSetting');

        $this->assertInstanceOf(SettingEntity::class, $setting);
        $this->assertEquals('testSetSetting', $setting->getValue());
    }

    /**
     * Tests that `set` method correctly determines and saves the type when using `SettingType::AUTO`.
     */
    public function testSetWithAutoType(): void
    {
        // Test with a string value
        $this->settingsManager->set('testSetWithAutoTypeString', 'stringValue', SettingType::AUTO);
        $setting = $this->settingsManager->get('testSetWithAutoTypeString');
        $this->assertNotNull($setting);
        $this->assertEquals(SettingType::STRING, $setting->getType());
        $this->assertEquals('stringValue', $setting->getValue());

        // Test with an integer value
        $this->settingsManager->set('testSetWithAutoTypeInteger', 123, SettingType::AUTO);
        $setting = $this->settingsManager->get('testSetWithAutoTypeInteger');
        $this->assertNotNull($setting);
        $this->assertEquals(SettingType::INTEGER, $setting->getType());
        $this->assertEquals(123, $setting->getValue());

        // Test with a boolean value
        $this->settingsManager->set('testSetWithAutoTypeBoolean', true, SettingType::AUTO);
        $setting = $this->settingsManager->get('testSetWithAutoTypeBoolean');
        $this->assertNotNull($setting);
        $this->assertEquals(SettingType::BOOLEAN, $setting->getType());
        $this->assertTrue($setting->getValue());

        // Test with a float value
        $this->settingsManager->set('testSetWithAutoTypeFloat', 1.23, SettingType::AUTO);
        $setting = $this->settingsManager->get('testSetWithAutoTypeFloat');
        $this->assertNotNull($setting);
        $this->assertEquals(SettingType::FLOAT, $setting->getType());
        $this->assertEquals(1.23, $setting->getValue());
    }

    public function testSetDuplicateSettingWithUpdateAllowed(): void
    {
        $this->settingsManager->set('testSetDuplicateSettingWithUpdateAllowed', 'initialValue', SettingType::STRING);
        $this->settingsManager->set('testSetDuplicateSettingWithUpdateAllowed', 'updatedValue', SettingType::STRING);

        $setting = $this->settingsManager->get('testSetDuplicateSettingWithUpdateAllowed');
        $this->assertEquals('updatedValue', $setting->getValue());
    }

    public function testSetDuplicateSettingWithoutUpdateAllowed(): void
    {
        $this->settingsManager->set('testSetDuplicateSettingWithoutUpdateAllowed', 'value', SettingType::STRING);

        $this->expectException(SettingAlreadyExistsException::class);
        $this->settingsManager->set('testSetDuplicateSettingWithoutUpdateAllowed', 'newValue', SettingType::STRING, false);
    }

    public function testUpdateSetting(): void
    {
        $this->settingsManager->set('testUpdateSetting', 'initialValue', SettingType::STRING);
        $this->settingsManager->update('testUpdateSetting', 'updatedValue');
        $setting = $this->settingsManager->get('testUpdateSetting');

        $this->assertEquals('updatedValue', $setting->getValue());
    }

    public function testUpdateNonExistentSettingThrowsException(): void
    {
        $this->expectException(SettingNotFoundException::class);
        $this->settingsManager->update('testUpdateNonExistentSettingThrowsException', 'value');
    }

    public function testUpdateSettingWithTypeOverride(): void
    {
        $this->settingsManager->set('testUpdateSettingWithTypeOverride', 'initialValue', SettingType::STRING);
        $this->settingsManager->update('testUpdateSettingWithTypeOverride', 123, UpdateType::OVERRIDE);

        $setting = $this->settingsManager->get('testUpdateSettingWithTypeOverride');
        $this->assertEquals(123, $setting->getValue());
        $this->assertEquals(SettingType::INTEGER, $setting->getType());
    }

    public function testUpdateSettingWithTypeKeepCurrent(): void
    {
        $this->settingsManager->set('testUpdateSettingWithTypeKeepCurrent', 'initialValue', SettingType::STRING);

        $this->expectException(SettingTypeMismatchException::class);
        $this->settingsManager->update('testUpdateSettingWithTypeKeepCurrent', 123, UpdateType::KEEP_CURRENT);
    }


    public function testRemoveSetting(): void
    {
        $this->settingsManager->set('testRemoveSetting', 'value', SettingType::STRING);
        $this->settingsManager->remove('testRemoveSetting');

        $setting = $this->settingsManager->get('testRemoveSetting');
        $this->assertNull($setting);
    }

    public function testRemoveNonExistentSettingThrowsException(): void
    {
        $this->expectException(SettingNotFoundException::class);
        $this->settingsManager->remove('testRemoveNonExistentSettingThrowsException');
    }

    public function testDetectTypeWithValidValues(): void
    {
        $this->assertEquals(SettingType::STRING, $this->invokeMethod($this->settingsManager, 'detectType', ['test']));
        $this->assertEquals(SettingType::INTEGER, $this->invokeMethod($this->settingsManager, 'detectType', [123]));
        $this->assertEquals(SettingType::BOOLEAN, $this->invokeMethod($this->settingsManager, 'detectType', [true]));
        $this->assertEquals(SettingType::FLOAT, $this->invokeMethod($this->settingsManager, 'detectType', [1.23]));
        $this->assertEquals(SettingType::JSON, $this->invokeMethod($this->settingsManager, 'detectType', ['{"key": "value"}']));
    }

    public function testExistsReturnsTrueForExistingSetting(): void
    {
        $this->settingsManager->set('testExistsReturnsTrueForExistingSetting', 'value', SettingType::STRING);

        $this->assertTrue($this->settingsManager->exists('testExistsReturnsTrueForExistingSetting'));
    }

    public function testExistsReturnsFalseForNonExistentSetting(): void
    {
        $this->assertFalse($this->settingsManager->exists('testExistsReturnsFalseForNonExistentSetting'));
    }

    public function testAllReturnsAllSettings(): void
    {
        $this->settingsManager->set('testAllReturnsAllSettings1', 'value1', SettingType::STRING);
        $this->settingsManager->set('testAllReturnsAllSettings2', 'value2', SettingType::INTEGER);

        $settings = $this->settingsManager->all();

        $this->assertCount(2, $settings);
        $this->assertInstanceOf(SettingEntity::class, $settings[0]);
        $this->assertInstanceOf(SettingEntity::class, $settings[1]);
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

    /**
     * Helper method to call private/protected methods.
     */
    private function invokeMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}