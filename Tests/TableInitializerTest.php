<?php

namespace Tests\Unit\Temant\SettingsManager\Utils;

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
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__], true);
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        $this->entityManager = new EntityManager($connection, $config);
    }

    public function testTableInitializerCanInitializeTable(): void
    {
        TableInitializer::init($this->entityManager, 'settings');

        $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
        $tables = $schemaManager->listTables();
        $tableNames = array_map(fn($table) => $table->getName(), $tables);

        $this->assertContains('settings', $tableNames);
    }

    public function testCannotAddAnExistingTable(): void
    {
        $tableName = 'settings';
        $this->assertTrue(TableInitializer::init($this->entityManager, $tableName));
        $this->assertFalse(TableInitializer::init($this->entityManager, $tableName));
    }

    public function testExceptionIsThrownOnInitializationFailure(): void
    {
        $this->expectException(SettingsTableInitializationException::class);
        
        TableInitializer::init($this->entityManager, 1);
    }
}