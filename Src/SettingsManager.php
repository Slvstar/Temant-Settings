<?php declare(strict_types=1);

namespace Temant\SettingsManager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\DBAL\Schema\SchemaException;
use Temant\SettingsManager\Entity\Settings;
use Temant\SettingsManager\Enum\SettingType;
use Temant\SettingsManager\Contract\SettingsInterface;

/**
 * SettingsManager is responsible for managing application settings,
 * including adding, retrieving, setting, updating, and deleting settings.
 */
final readonly class SettingsManager implements SettingsInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private string $tableName = "settings"
    ) {
        $this->initializeSettingsTable();
    }

    /**
     * Adds a new setting. Throws an exception if the setting already exists.
     *
     * @param string $name The name of the setting to add.
     * @param SettingType $type The type of the setting value.
     * @param mixed $value The value to set.
     * @throws \RuntimeException if the setting already exists.
     */
    public function add(string $name, SettingType $type, mixed $value): void
    {
        if ($this->getSetting($name)) {
            throw new \RuntimeException("A setting with the name '$name' already exists.");
        }

        $setting = new Settings($name, $type, $value);
        $this->entityManager->persist($setting);
        $this->entityManager->flush();
    }

    /**
     * Sets or updates a setting value. Creates the setting if it doesn't exist.
     *
     * @param string $name The name of the setting to set.
     * @param SettingType $type The type of the setting value.
     * @param mixed $value The value to set.
     */
    public function set(string $name, SettingType $type, mixed $value): void
    {
        $setting = $this->getSetting($name);

        if ($setting) {
            // Update existing setting
            $setting->setType($type);
            $setting->setValue($value);
        } else {
            // Create a new setting
            $setting = new Settings($name, $type, $value);
            $this->entityManager->persist($setting);
        }

        $this->entityManager->flush();
    }

    /**
     * Updates an existing setting. Throws an exception if the setting does not exist.
     *
     * @param string $name The name of the setting to update.
     * @param mixed $value The new value to set.
     * @throws \RuntimeException if the setting does not exist.
     */
    public function update(string $name, mixed $value): void
    {
        $setting = $this->getSetting($name);

        if (!$setting) {
            throw new \RuntimeException("Cannot update. No setting found with the name '$name'.");
        }

        $setting->setValue($value);
        $this->entityManager->flush();
    }

    /**
     * Retrieves a setting value by its key.
     *
     * @param string $key The key for the desired setting.
     * @return mixed The value of the setting if found, or null if the key does not exist.
     */
    public function get(string $key): mixed
    {
        $setting = $this->getSetting($key);
        return $setting ? $setting->getValue() : null;
    }

    /**
     * Checks if a setting exists by its key.
     *
     * @param string $key The key to check in the settings.
     * @return bool True if the setting exists, false otherwise.
     */
    public function exists(string $key): bool
    {
        return $this->getSetting($key) !== null;
    }

    /**
     * Removes a setting by its key.
     *
     * @param string $key The key of the setting to be removed.
     */
    public function remove(string $key): void
    {
        $this->removeSetting($key);
    }

    /**
     * Initializes the settings table in the database.
     */
    public function initializeSettingsTable(): void
    {
        try {
            $metadata = $this->entityManager->getClassMetadata(Settings::class);
            $schemaTool = new SchemaTool($this->entityManager);
            $schemaManager = $this->entityManager->getConnection()->createSchemaManager();

            $metadata->setTableName($this->tableName);

            if (!$schemaManager->tablesExist([$metadata->getTableName()])) {
                $schemaTool->createSchema([$metadata]);
            }

            $defaultSettings = [
                ['site_name', SettingType::STRING, 'Default Site Name'],
                ['admin_email', SettingType::STRING, 'admin@example.com'],
            ];

            foreach ($defaultSettings as [$name, $type, $value]) {
                if ($this->getSetting($name) === null) {
                    $this->set($name, $type, $value);
                }
            }
        } catch (SchemaException $e) {
            throw new \RuntimeException('Failed to initialize settings table: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException('An error occurred during settings table initialization: ' . $e->getMessage());
        }
    }

    /**
     * Retrieves all settings from the database.
     *
     * @return Settings[] An array of all settings.
     */
    public function all(): array
    {
        return $this->entityManager->getRepository(Settings::class)->findAll();
    }

    // Additional internal methods

    /**
     * Retrieves a setting by its name.
     *
     * @param string $name The name of the setting to retrieve.
     * @return Settings|null The setting entity, or null if not found.
     */
    private function getSetting(string $name): ?Settings
    {
        return $this->entityManager->getRepository(Settings::class)->findOneBy(['name' => $name]);
    }

    /**
     * Removes a setting by its name.
     *
     * @param string $name The name of the setting to remove.
     */
    private function removeSetting(string $name): void
    {
        $setting = $this->getSetting($name);

        if ($setting) {
            $this->entityManager->remove($setting);
            $this->entityManager->flush();
        }
    }

    /**
     * Determines the appropriate SettingType for a given value.
     *
     * @param mixed $value The value to determine the type for.
     * @return SettingType The determined SettingType.
     */
    private function determineType(mixed $value): SettingType
    {
        return match (true) {
            is_int($value) => SettingType::INTEGER,
            is_bool($value) => SettingType::BOOLEAN,
            is_float($value) => SettingType::FLOAT,
            is_string($value) => SettingType::STRING,
            default => SettingType::STRING,
        };
    }
}