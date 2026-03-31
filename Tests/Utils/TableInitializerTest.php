<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Tests\Utils;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\TestCase;
use Temant\SettingsManager\Exception\SettingsTableInitializationException;
use Temant\SettingsManager\Utils\TableInitializer;

final class TableInitializerTest extends TestCase
{
    private EntityManagerInterface $entityManager;

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

        $this->entityManager = new EntityManager($connection, $config);
    }

    public function testCreateTableReturnsTrueFirstTime(): void
    {
        $this->assertTrue(TableInitializer::init($this->entityManager, 'settings'));
    }

    public function testCreateTableReturnsFalseIfAlreadyExists(): void
    {
        TableInitializer::init($this->entityManager, 'settings');
        $this->assertFalse(TableInitializer::init($this->entityManager, 'settings'));
    }

    public function testExceptionIsThrownOnInitializationFailure(): void
    {
        $this->expectException(SettingsTableInitializationException::class);

        // Use an empty string which will cause a DB-level error during table creation
        TableInitializer::init($this->entityManager, '');
    }
}
