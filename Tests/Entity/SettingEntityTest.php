<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Tests\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Temant\SettingsManager\Entity\SettingEntity;
use Temant\SettingsManager\Enum\SettingType;

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

        $entity->setValue('stringValue');
        $this->assertEquals('stringValue', $entity->getValue());

        $entity->setType(SettingType::INTEGER);
        $entity->setValue(123);
        $this->assertEquals(123, $entity->getValue());

        $entity->setType(SettingType::BOOLEAN);
        $entity->setValue(true);
        $this->assertTrue($entity->getValue());

        $entity->setType(SettingType::FLOAT);
        $entity->setValue(1.23);
        $this->assertEquals(1.23, $entity->getValue());

        $entity->setType(SettingType::JSON);
        $entity->setValue('{"key":"value"}');
        $this->assertEquals(['key' => 'value'], $entity->getValue());
    }

    public function testSetValueWithArray(): void
    {
        $entity = new SettingEntity('tags', SettingType::ARRAY, ['php', 'doctrine']);

        $this->assertEquals(['php', 'doctrine'], $entity->getValue());
        $this->assertEquals('["php","doctrine"]', $entity->getRawValue());
    }

    public function testSetValueWithDatetime(): void
    {
        $date = new DateTimeImmutable('2026-01-15T10:30:00+00:00');
        $entity = new SettingEntity('launch_date', SettingType::DATETIME, $date);

        $retrieved = $entity->getValue();
        $this->assertInstanceOf(DateTimeImmutable::class, $retrieved);
        $this->assertEquals('2026-01-15', $retrieved->format('Y-m-d'));
    }

    public function testSetType(): void
    {
        $entity = new SettingEntity('testName', SettingType::STRING, 'testValue');

        $entity->setType(SettingType::INTEGER);

        $this->assertEquals(SettingType::INTEGER, $entity->getType());
    }

    public function testDescriptionAndGroup(): void
    {
        $entity = new SettingEntity('testName', SettingType::STRING, 'testValue');

        $this->assertNull($entity->getDescription());
        $this->assertNull($entity->getGroup());

        $entity->setDescription('A test setting');
        $entity->setGroup('testing');

        $this->assertEquals('A test setting', $entity->getDescription());
        $this->assertEquals('testing', $entity->getGroup());
    }

    public function testToString(): void
    {
        $entity = new SettingEntity('testName', SettingType::STRING, 'testValue');

        $this->assertIsString((string) $entity);
        $this->assertEquals('testValue', (string) $entity);
    }

    public function testGetRawValue(): void
    {
        $entity = new SettingEntity('flag', SettingType::BOOLEAN, true);

        $this->assertEquals('true', $entity->getRawValue());
        $this->assertTrue($entity->getValue());
    }

    public function testToArrayReturnsSerializableData(): void
    {
        $entity = new SettingEntity('testName', SettingType::STRING, 'testValue');
        $entity->setDescription('desc');
        $entity->setGroup('grp');

        $arr = $entity->__toArray();

        $this->assertIsArray($arr);
        $this->assertEquals('testName', $arr['name']);
        $this->assertEquals('testValue', $arr['value']);
        $this->assertEquals('string', $arr['type']);
        $this->assertEquals('desc', $arr['description']);
        $this->assertEquals('grp', $arr['group']);

        // Dates should be ISO 8601 strings, not objects
        $this->assertIsString($arr['createdAt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $arr['createdAt']);
    }

    public function testFluentInterface(): void
    {
        $entity = new SettingEntity('key', SettingType::STRING, 'val');

        $result = $entity->setName('key2')
            ->setType(SettingType::INTEGER)
            ->setValue(42)
            ->setDescription('desc')
            ->setGroup('grp');

        $this->assertSame($entity, $result);
    }
}
