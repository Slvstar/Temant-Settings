<?php declare(strict_types=1);

namespace Temant\SettingsManager {

    use Doctrine\ORM\EntityManagerInterface;
    use InvalidArgumentException;
    use Temant\SettingsManager\Entity\SettingEntity;
    use Temant\SettingsManager\Enum\SettingType;
    use Temant\SettingsManager\Enum\UpdateType;
    use Temant\SettingsManager\Exception\SettingAlreadyExistsException;
    use Temant\SettingsManager\Exception\SettingNotFoundException;
    use Temant\SettingsManager\Exception\SettingTypeMismatchException;
    use Temant\SettingsManager\Utils\TableInitializer;

    /**
     * SettingsManager is responsible for managing application settings,
     * including adding, retrieving, updating, and deleting settings.
     */
    final class SettingsManager
    {
        /**
         * Constructor for SettingsManager.
         *
         * @param EntityManagerInterface $entityManager The Doctrine entity manager.
         * @param string $tableName The name of the settings table. Default is "settings".
         * @param array<string, array{value: mixed, type?: SettingType}> $defaultSettings Optional default settings
         * in the format ['key' => ['value' => '...', 'type' => SettingType::...]].
         */
        public function __construct(
            private EntityManagerInterface $entityManager,
            public string $tableName = "settings",
            private array $defaultSettings = []
        ) {
            TableInitializer::init($this->entityManager, $this->tableName);
            $this->initializeDefaults();
        }

        /**
         * Initializes default settings if they are not already present in the database.
         *
         * @return void
         */
        private function initializeDefaults(): void
        {
            foreach ($this->defaultSettings as $name => $data) {
                if (!$this->exists($name)) {
                    $this->set(
                        $name,
                        $data['value'],
                        $data['type'] ?? SettingType::AUTO,
                        false // Do not allow updates for defaults
                    );
                }
            }
        }

        /**
         * Sets or adds a setting value. Updates if the setting exists, otherwise creates it.
         *
         * @param string $name The name of the setting.
         * @param mixed $value The value to set.
         * @param SettingType $type The type of the setting value.
         * @param bool $allowUpdate Whether to allow updating an existing setting. Defaults to true.
         * @return self Fluent interface, returns the instance of the `SettingsManager`.
         * @throws SettingAlreadyExistsException If $allowUpdate is false and the setting already exists.
         */
        public function set(string $name, mixed $value, SettingType $type = SettingType::AUTO, bool $allowUpdate = true): self
        {
            if ($type === SettingType::AUTO) {
                $type = $this->detectType($value);
            }

            $setting = $this->get($name);

            if ($allowUpdate && $setting) {
                $setting->setType($type)->setValue($value);
            } elseif (!$allowUpdate && $setting) {
                throw new SettingAlreadyExistsException("A setting with the name '$name' already exists.");
            } else {
                // Create a new setting
                $setting = new SettingEntity($name, $type, $value);
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
         * @param UpdateType $updateType Whether to update the type if it changes. Defaults to false.
         * @return SettingEntity The updated setting.
         * @throws SettingNotFoundException If the setting does not exist.
         */
        public function update(string $name, mixed $value, UpdateType $updateType = UpdateType::OVERRIDE): SettingEntity
        {
            $setting = $this->get($name);

            if ($setting === null) {
                throw new SettingNotFoundException("Cannot update. No setting found with the name '$name'.");
            }

            if ($updateType === UpdateType::OVERRIDE) {
                $newType = $this->detectType($value);
                $setting->setType($newType);
            }

            $this->validateType($setting->getType(), $value);
            $setting->setValue($value);

            $this->entityManager->flush();

            return $setting;
        }

        /**
         * Retrieves a setting by its key.
         *
         * @param string $key The key of the desired setting.
         * @return SettingEntity|null The setting entity, or null if not found.
         */
        public function get(string $key): ?SettingEntity
        {
            return $this->entityManager
                ->getRepository(SettingEntity::class)
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
         * @return SettingEntity[] An array of all settings.
         */
        public function all(): array
        {
            return $this->entityManager
                ->getRepository(SettingEntity::class)
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

        /**
         * Automatically determines the type of the given value for setting purposes.
         *
         * @param mixed $value The value for which to determine the type.
         * @return SettingType The detected setting type.
         * @throws InvalidArgumentException If the value type is unsupported.
         */
        private function detectType(mixed $value): SettingType
        {
            return match (true) {
                $value instanceof SettingType => $value,
                is_string($value) && json_validate($value) => SettingType::JSON,
                is_string($value) => SettingType::STRING,
                is_int($value) => SettingType::INTEGER,
                is_bool($value) => SettingType::BOOLEAN,
                is_float($value) => SettingType::FLOAT,
                default => throw new InvalidArgumentException("Unsupported value type for auto-detection."),
            };
        }

        /**
         * Validates that the given value matches the expected SettingType.
         *
         * @param SettingType $expectedType The expected type of the value.
         * @param mixed $value The value to validate.
         * @throws SettingTypeMismatchException if the value does not match the expected type.
         */
        private function validateType(SettingType $expectedType, mixed $value): void
        {
            if ($expectedType === SettingType::AUTO) {
                return;
            }

            $isValid = match ($expectedType) {
                SettingType::JSON => is_string($value) && json_validate($value),
                SettingType::STRING => is_string($value),
                SettingType::INTEGER => is_int($value),
                SettingType::BOOLEAN => is_bool($value),
                SettingType::FLOAT => is_float($value),
            };

            if (!$isValid) {
                throw new SettingTypeMismatchException(
                    sprintf("Expected type {%s} but got {%s}", $expectedType->value, gettype($value))
                );
            }
        }
    }
}