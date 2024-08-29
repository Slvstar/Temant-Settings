<?php declare(strict_types=1);

namespace Temant\SettingsManager\Contract {
    use Temant\SettingsManager\Entity\Settings;
    use Temant\SettingsManager\Enum\SettingType;

    /**
     * Interface for the Settings management system.
     */
    interface SettingsInterface
    {
        /**
         * Adds a new setting. Throws an exception if the setting already exists.
         *
         * @param string $name The name of the setting to add.
         * @param SettingType $type The type of the setting value.
         * @param mixed $value The value to set.
         * @throws \RuntimeException if the setting already exists.
         */
        public function add(string $name, SettingType $type, mixed $value): void;

        /**
         * Sets or updates a setting value. Creates the setting if it doesn't exist.
         *
         * @param string $name The name of the setting to set.
         * @param SettingType $type The type of the setting value.
         * @param mixed $value The value to set.
         */
        public function set(string $name, SettingType $type, mixed $value): void;

        /**
         * Updates an existing setting. Throws an exception if the setting does not exist.
         *
         * @param string $name The name of the setting to update.
         * @param mixed $value The new value to set.
         * @throws \RuntimeException if the setting does not exist.
         */
        public function update(string $name, mixed $value): void;

        /**
         * Retrieves a setting value by its key.
         *
         * @param string $key The key for the desired setting.
         * @return Settings|null The value of the setting if found, or null if the key does not exist.
         */
        public function get(string $key): ?Settings;

        /**
         * Checks if a setting exists by its key.
         *
         * @param string $key The key to check in the settings.
         * @return bool True if the setting exists, false otherwise.
         */
        public function exists(string $key): bool;

        /**
         * Removes a setting by its key.
         *
         * @param string $key The key of the setting to be removed.
         */
        public function remove(string $key): void;

        /**
         * Retrieves all settings from the database.
         *
         * @return array An array of all settings.
         */
        public function all(): array;
    }
}