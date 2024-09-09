<?php declare(strict_types=1);

namespace Temant\SettingsManager {

    use Doctrine\ORM\EntityManagerInterface;
    use Temant\SettingsManager\Entity\Setting;
    use Temant\SettingsManager\Enum\SettingType;
    use Temant\SettingsManager\Exception\SettingAlreadyExistsException;
    use Temant\SettingsManager\Exception\SettingNotFoundException;
    use Temant\SettingsManager\Utils\TableInitializer;

    /**
     * SettingsManager is responsible for managing application settings,
     * including adding, retrieving, updating, and deleting settings.
     */
    final readonly class SettingsManager
    {
        /**
         * Constructor for SettingsManager.
         *
         * @param EntityManagerInterface $entityManager The Doctrine entity manager.
         * @param string $tableName The name of the settings table. Default is "settings".
         */
        public function __construct(
            private EntityManagerInterface $entityManager,
            private string $tableName = "settings"
        ) {
            TableInitializer::init($this->entityManager, $this->tableName);
        }

        /**
         * Adds a new setting. Throws an exception if the setting already exists.
         *
         * @param string $name The name of the setting.
         * @param SettingType $type The type of the setting value.
         * @param mixed $value The value of the setting.
         * @return self Fluent interface, returns the instance of the `SettingsManager`.
         * @throws SettingAlreadyExistsException If the setting already exists.
         */
        public function add(string $name, SettingType $type, mixed $value): self
        {
            if ($this->exists($name)) {
                throw new SettingAlreadyExistsException("A setting with the name '$name' already exists.");
            }

            $setting = new Setting($name, $type, $value);
            $this->entityManager->persist($setting);
            $this->entityManager->flush();

            return $this;
        }

        /**
         * Sets or updates a setting value. Creates the setting if it doesn't exist.
         *
         * @param string $name The name of the setting.
         * @param SettingType $type The type of the setting value.
         * @param mixed $value The value to set.
         * @return self Fluent interface, returns the instance of the `SettingsManager`.
         */
        public function set(string $name, SettingType $type, mixed $value): self
        {
            $setting = $this->get($name);

            if ($setting !== null) {
                // Update existing setting
                $setting->setType($type);
                $setting->setValue($value);
            } else {
                // Create a new setting
                $setting = new Setting($name, $type, $value);
                $this->entityManager->persist($setting);
            }

            $this->entityManager->flush();

            return $this;
        }

        /**
         * Updates an existing setting. Throws an exception if the setting does not exist.
         *
         * @param string $name The name of the setting.
         * @param mixed $value The new value of the setting.
         * @return Setting The updated setting.
         * @throws SettingNotFoundException If the setting does not exist.
         */
        public function update(string $name, mixed $value): Setting
        {
            $setting = $this->get($name);

            if ($setting === null) {
                throw new SettingNotFoundException("Cannot update. No setting found with the name '$name'.");
            }

            $setting->setValue($value);
            $this->entityManager->flush();

            return $setting;
        }

        /**
         * Retrieves a setting by its key.
         *
         * @param string $key The key of the desired setting.
         * @return Setting|null The setting entity, or null if not found.
         */
        public function get(string $key): ?Setting
        {
            return $this->entityManager
                ->getRepository(Setting::class)
                ->findOneBy(['name' => $key]);
        }

        /**
         * Checks if a setting exists by its key.
         *
         * @param string $key The key to check in the settings.
         * @return bool True if the setting exists, false otherwise.
         */
        public function exists(string $key): bool
        {
            return $this->get($key) !== null;
        }

        /**
         * Retrieves all settings from the database.
         *
         * @return Setting[] An array of all settings.
         */
        public function all(): array
        {
            return $this->entityManager
                ->getRepository(Setting::class)
                ->findAll();
        }

        /**
         * Removes a setting by its key.
         *
         * @param string $key The key of the setting to remove.
         * @return self Fluent interface, returns the instance of the `SettingsManager`.
         * @throws SettingNotFoundException If the setting does not exist.
         */
        public function remove(string $key): self
        {
            $setting = $this->get($key);

            if ($setting === null) {
                throw new SettingNotFoundException("Cannot remove. No setting found with the key '$key'.");
            }

            $this->entityManager->remove($setting);
            $this->entityManager->flush();

            return $this;
        }
    }
}