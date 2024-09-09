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
         * @param ?string $tableName The name of the settings table.
         * @throws SettingsTableInitializationException If an error occurs during initialization.
         */
        public static function init(EntityManagerInterface $entityManager, ?string $tableName = null): void
        {
            try {
                // Retrieve metadata for the SettingEntity entity
                $metadata = $entityManager->getClassMetadata(SettingEntity::class);

                // Adjust table name based on the provided tableName
                if ($tableName) {
                    $metadata->setPrimaryTable(['name' => $tableName]);
                }

                // Create schema manager instance
                $schemaManager = $entityManager->getConnection()->createSchemaManager();

                // Early return if the settings table already exists
                if ($schemaManager->tableExists($tableName)) {
                    throw new SettingsTableInitializationException("Table $tableName already exists!");
                }

                // Create schema tool
                $schemaTool = new SchemaTool($entityManager);

                // Create the schema for the table
                $schemaTool->createSchema([$metadata]);
            } catch (Throwable $e) {
                // Throw a custom exception in case of failure
                throw new SettingsTableInitializationException("An error occurred during settings table initialization: {$e->getMessage()}");
            }
        }
    }
}