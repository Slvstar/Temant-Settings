<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Utils;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Temant\SettingsManager\Entity\SettingEntity;
use Temant\SettingsManager\Exception\SettingsTableInitializationException;
use Throwable;

/**
 * Creates and migrates the settings database table.
 *
 * Supports MySQL, PostgreSQL, and SQLite drivers. The table is excluded from
 * Doctrine's schema asset filter so that `doctrine:migrations:diff` and
 * `doctrine:schema:update` do not drop or alter it.
 */
final class TableInitializer
{
    /** Columns that may be missing on tables created by older library versions. */
    private const array OPTIONAL_COLUMNS = [
        'description'   => 'VARCHAR(500) DEFAULT NULL',
        'setting_group' => 'VARCHAR(255) DEFAULT NULL',
    ];

    /**
     * Ensure the settings table exists and is up-to-date, creating or migrating it when necessary.
     *
     * @param EntityManagerInterface $entityManager Active Doctrine entity manager.
     * @param string                 $tableName     Desired table name.
     *
     * @return bool `true` if the table was created, `false` if it already existed.
     *
     * @throws SettingsTableInitializationException On any failure.
     */
    public static function init(EntityManagerInterface $entityManager, string $tableName): bool
    {
        try {
            self::installSchemaAssetFilter($entityManager, $tableName);

            $metadata = $entityManager->getClassMetadata(SettingEntity::class);
            $metadata->setPrimaryTable(['name' => $tableName]);

            $driver = self::getDriver($entityManager);

            if (self::tableExists($entityManager, $driver, $tableName)) {
                self::migrateColumns($entityManager->getConnection(), $tableName);
                return false;
            }

            $schemaTool = new SchemaTool($entityManager);
            $schemaTool->createSchema([$metadata]);

            return true;
        } catch (SettingsTableInitializationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SettingsTableInitializationException(
                "An error occurred during settings table initialization: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Install a schema asset filter that hides the settings table from Doctrine's
     * schema tooling (`schema:update`, `migrations:diff`, `schema:validate`).
     *
     * Preserves any filter the host application has already registered.
     */
    private static function installSchemaAssetFilter(
        EntityManagerInterface $entityManager,
        string $tableName,
    ): void {
        $config = $entityManager->getConfiguration();
        $existingFilter = $config->getSchemaAssetsFilter();

        $config->setSchemaAssetsFilter(
            static function (string $assetName) use ($tableName, $existingFilter): bool {
                if ($assetName === $tableName) {
                    return false;
                }

                return (bool) $existingFilter($assetName);
            },
        );
    }

    /**
     * Add any columns that are missing from an existing table.
     *
     * This handles seamless upgrades from older library versions that did not
     * have `description` or `setting_group` columns.
     */
    private static function migrateColumns(Connection $connection, string $tableName): void
    {
        $schemaManager = $connection->createSchemaManager();
        $existingColumns = self::getColumnNames($schemaManager, $tableName);

        foreach (self::OPTIONAL_COLUMNS as $column => $definition) {
            if (!in_array($column, $existingColumns, true)) {
                $connection->executeStatement(
                    "ALTER TABLE {$connection->quoteIdentifier($tableName)} ADD {$connection->quoteIdentifier($column)} $definition",
                );
            }
        }
    }

    /**
     * Get the list of column names for a table (lowercased for case-insensitive comparison).
     *
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     * @return string[]
     */
    private static function getColumnNames(AbstractSchemaManager $schemaManager, string $tableName): array
    {
        $columns = $schemaManager->listTableColumns($tableName);

        return array_map(
            static fn($col): string => strtolower($col->getName()),
            $columns,
        );
    }

    /**
     * Resolve the database driver from the connection parameters.
     *
     * @throws SettingsTableInitializationException If no driver is configured.
     */
    private static function getDriver(EntityManagerInterface $entityManager): string
    {
        $params = $entityManager->getConnection()->getParams();

        if (!isset($params['driver'])) {
            throw new SettingsTableInitializationException('Database driver not defined in connection params.');
        }

        return $params['driver'];
    }

    /**
     * Checks whether the given table already exists in the database.
     *
     * @throws SettingsTableInitializationException For unsupported drivers.
     */
    private static function tableExists(
        EntityManagerInterface $entityManager,
        string $driver,
        string $tableName,
    ): bool {
        $connection = $entityManager->getConnection();
        $quoted = $connection->quote($tableName);

        $sql = match ($driver) {
            'pdo_mysql'  => "SHOW TABLES LIKE $quoted",
            'pdo_pgsql'  => "SELECT tablename FROM pg_tables WHERE tablename = $quoted",
            'pdo_sqlite' => "SELECT name FROM sqlite_master WHERE type='table' AND name = $quoted",
            default      => throw new SettingsTableInitializationException("Unsupported database driver: $driver"),
        };

        return (bool) $connection->fetchOne($sql);
    }
}
