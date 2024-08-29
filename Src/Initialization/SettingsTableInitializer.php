<?php declare(strict_types=1);

namespace Temant\SettingsManager\Initialization {

    use Throwable;
    use Doctrine\ORM\EntityManagerInterface;
    use Doctrine\ORM\Tools\SchemaTool;
    use Temant\SettingsManager\Entity\Setting;
    use Temant\SettingsManager\Exception\SettingsTableInitializationException;

    /**
     * SettingsTableInitializer is responsible for ensuring that the settings table
     * exists in the database and initializing it if necessary.
     */
    final class SettingsTableInitializer
    {
        /**
         * Constructor for SettingsTableInitializer.
         *
         * @param EntityManagerInterface $entityManager The Doctrine entity manager.
         * @param string $tableName The name of the settings table. Defaults to "settings".
         */
        public function __construct(
            private EntityManagerInterface $entityManager,
            private string $tableName = "settings"
        ) {
        }

        /**
         * Initializes the settings table in the database if it does not already exist.
         *
         * This method checks whether the settings table exists in the database. If the table does
         * not exist, it creates the table schema based on the `Setting` entity metadata.
         *
         * @throws SettingsTableInitializationException If an error occurs during initialization.
         */
        public function initialize(): void
        {
            try {
                // Retrieve metadata for the Setting entity
                $metadata = $this->entityManager->getClassMetadata(Setting::class);

                // Adjust table name based on the provided tableName
                $metadata->setTableName($this->tableName);

                // Create schema tool and schema manager instances
                $schemaTool = new SchemaTool($this->entityManager);
                $schemaManager = $this->entityManager->getConnection()->createSchemaManager();

                // Check if the settings table already exists; create it if it does not exist
                if (!$schemaManager->tablesExist([$this->tableName])) {
                    $schemaTool->createSchema([$metadata]);
                }
            } catch (Throwable $e) {
                // Throw a custom exception in case of failure
                throw new SettingsTableInitializationException("An error occurred during settings table initialization: {$e->getMessage()}");
            }
        }
    }
}