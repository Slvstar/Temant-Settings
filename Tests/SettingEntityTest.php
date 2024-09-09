<?php declare(strict_types=1);

namespace Temant\SettingsManager\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Temant\SettingsManager\Entity\SettingEntity;
use Temant\SettingsManager\Enum\SettingType;
use DateTimeImmutable;

class SettingEntityTest extends TestCase
{
    public function testConstructorInitializesCorrectly(): void
    {
        $entity = new SettingEntity('testName', SettingType::STRING, 'testValue');

        $this->assertEquals('testName', $entity->getName());
        $this->assertEquals(SettingType::STRING, $entity->getType());
        $this->assertEquals('testValue', $entity->getValue());
        $this->assertInstanceOf(DateTimeImmutable::class, $entity->getCreatedAt());
        $this->assertNotNull($entity->getUpdatedAt());
    }

    public function testSetNameUpdatesTimestamp(): void
    {
        $entity = new SettingEntity('testName', SettingType::STRING, 'testValue');

        $initialTimestamp = $entity->getUpdatedAt();

        $entity->setName('newName');

        $this->assertEquals('newName', $entity->getName());
        $this->assertNotEquals($initialTimestamp, $entity->getUpdatedAt());
    }

    public function testSetValueWithDifferentTypes(): void
    {
        $entity = new SettingEntity('testName', SettingType::STRING);

        // Test with string
        $entity->setValue('stringValue');
        $this->assertEquals('stringValue', $entity->getValue());

        // Test with integer
        $entity->setType(SettingType::INTEGER);
        $entity->setValue(123);
        $this->assertEquals(123, $entity->getValue());

        // Test with boolean
        $entity->setType(SettingType::BOOLEAN);
        $entity->setValue(true);
        $this->assertTrue($entity->getValue());

        // Test with float
        $entity->setType(SettingType::FLOAT);
        $entity->setValue(1.23);
        $this->assertEquals(1.23, $entity->getValue());

        // Test with JSON
        $entity->setType(SettingType::JSON);
        $entity->setValue('{"key":"value"}');
        $this->assertEquals(['key' => 'value'], $entity->getValue());
    }

    public function testSetType(): void
    {
        $entity = new SettingEntity('testName', SettingType::STRING, 'testValue');

        $entity->setType(SettingType::INTEGER);

        $this->assertEquals(SettingType::INTEGER, $entity->getType());
    }

    public function testToString(): void
    {
        $entity = new SettingEntity('testName', SettingType::STRING, 'testValue');

        $this->assertEquals('testValue', (string) $entity);
    }
}