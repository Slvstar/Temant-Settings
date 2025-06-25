<?php declare(strict_types=1);

namespace Temant\SettingsManager\Utils {

    use Throwable;
    use Doctrine\ORM\EntityManagerInterface;
    use Doctrine\ORM\Tools\SchemaTool;
    use Temant\SettingsManager\Entity\SettingEntity;
    use Temant\SettingsManager\Exception\SettingsTableInitializationException;

    /**
     * TableInitializer is responsible for ensuring that the settings table
     * exists in the database and initializing it if necessary.
     */
    final class TableInitializer
    {
        /**
         * Initializes the settings table in the database if it does not already exist.
         *
         * @param EntityManagerInterface $entityManager The Doctrine entity manager.
         * @param string $tableName The name of the settings table.
         * @throws SettingsTableInitializationException If an error occurs during initialization.
         */
        public static function init(EntityManagerInterface &$entityManager, string $tableName): bool
        {
            try {
                // Tell the entity manager to ignore the created tables ..
                $entityManager->getConfiguration()
                    ->setSchemaAssetsFilter(
                        fn(string $tName): bool => $tName !== $tableName
                    );

                // Retrieve metadata for the SettingEntity entity
                $metadata = $entityManager->getClassMetadata(SettingEntity::class);

                // Adjust table name based on the provided tableName
                $metadata->setPrimaryTable(['name' => $tableName]);

                // Get the current driver type ..
                $params = $entityManager->getConnection()->getParams();

                if (!isset($params['driver'])) {
                    throw new \RuntimeException("Database driver not defined in connection params.");
                }

                $driver = $params['driver'];
                
                // Early return if the settings table already exists 
                switch ($driver) {
                    case 'pdo_mysql':
                        $quoted = $entityManager->getConnection()->quote($tableName);
                        $sql = "SHOW TABLES LIKE $quoted";
                        break;

                    case 'pdo_sqlite':
                        $quoted = $entityManager->getConnection()->quote($tableName);
                        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name = $quoted";
                        break;

                    default:
                        throw new \RuntimeException("Unsupported DB driver: $driver");
                }

                if ($entityManager->getConnection()->fetchOne($sql)) {
                    return false;
                }

                // Create schema tool
                $schemaTool = new SchemaTool($entityManager);

                // Create the schema for the table
                $schemaTool->createSchema([$metadata]);

                return true;
            } catch (Throwable $e) {
                // Throw a custom exception in case of failure
                throw new SettingsTableInitializationException("An error occurred during settings table initialization: {$e->getMessage()}");
            }
        }
    }
}