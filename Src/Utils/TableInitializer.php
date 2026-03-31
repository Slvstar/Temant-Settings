<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Temant\SettingsManager\Entity\SettingEntity;
use Temant\SettingsManager\Exception\SettingsTableInitializationException;
use Throwable;

/**
 * Creates the settings database table if it does not already exist.
 *
 * Supports MySQL, PostgreSQL, and SQLite drivers. The table is excluded from
 * Doctrine's schema asset filter so that `doctrine:migrations:diff` ignores it.
 */
final class TableInitializer
{
    /**
     * Ensure the settings table exists, creating it when necessary.
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
            // Exclude the settings table from Doctrine's schema operations
            // (migrations:diff, schema:update, schema:validate) so the host
            // application does not drop or alter the table we manage ourselves.
            // Preserve any existing filter the host application may have set.
            $config = $entityManager->getConfiguration();
            $existingFilter = $config->getSchemaAssetsFilter();

            $config->setSchemaAssetsFilter(
                static function (string $assetName) use ($tableName, $existingFilter): bool {
                    // Hide our table from Doctrine's schema tooling.
                    if ($assetName === $tableName) {
                        return false;
                    }

                    // Delegate to any previously registered filter.
                    return (bool) $existingFilter($assetName);
                },
            );

            $metadata = $entityManager->getClassMetadata(SettingEntity::class);
            $metadata->setPrimaryTable(['name' => $tableName]);

            $params = $entityManager->getConnection()->getParams();

            if (!isset($params['driver'])) {
                throw new SettingsTableInitializationException('Database driver not defined in connection params.');
            }

            if (self::tableExists($entityManager, $params['driver'], $tableName)) {
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
