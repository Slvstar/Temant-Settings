<?php

declare(strict_types=1);

namespace Temant\SettingsManager;

use Countable;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Temant\SettingsManager\Entity\SettingEntity;
use Temant\SettingsManager\Enum\SettingType;
use Temant\SettingsManager\Enum\UpdateType;
use Temant\SettingsManager\Exception\SettingAlreadyExistsException;
use Temant\SettingsManager\Exception\SettingNotFoundException;
use Temant\SettingsManager\Exception\SettingTypeMismatchException;
use Temant\SettingsManager\Utils\SettingsExporter;
use Temant\SettingsManager\Utils\SettingsImporter;
use Temant\SettingsManager\Utils\TableInitializer;

/**
 * Manages application settings persisted via Doctrine ORM.
 *
 * Provides full CRUD, bulk operations, in-memory caching, group-based
 * organisation, import/export, and automatic type detection.
 *
 * ```php
 * $manager = new SettingsManager($entityManager);
 *
 * $manager
 *     ->set('site.name', 'Acme Corp', group: 'site')
 *     ->set('site.debug', false, group: 'site')
 *     ->set('cache.ttl', 3600, group: 'cache');
 *
 * $name  = $manager->getValue('site.name');           // 'Acme Corp'
 * $ttl   = $manager->getOrDefault('cache.ttl', 60);   // 3600
 * $theme = $manager->getOrDefault('ui.theme', 'dark'); // 'dark' (not set)
 * ```
 */
final class SettingsManager implements Countable
{
    /** @var array<string, SettingEntity> In-memory identity map keyed by setting name. */
    private array $cache = [];

    /**
     * @param EntityManagerInterface $entityManager Doctrine entity manager.
     * @param string $tableName Database table name for settings.
     * @param array<string, array{value: mixed, type?: SettingType, description?: string, group?: string}> $defaultSettings
     *        Settings to seed on first run — existing keys are never overwritten.
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        public string $tableName = 'settings',
        private array $defaultSettings = [],
    ) {
        TableInitializer::init($this->entityManager, $this->tableName);
        $this->initializeDefaults();
    }

    // -------------------------------------------------------------------------
    //  Core CRUD
    // -------------------------------------------------------------------------

    /**
     * Create or update a setting.
     *
     * @param string      $name        Unique setting key.
     * @param mixed       $value       The value to store.
     * @param SettingType $type        Type hint — defaults to {@see SettingType::AUTO}.
     * @param bool        $allowUpdate When `false`, throws if the key already exists.
     * @param string|null $description Optional human-readable description.
     * @param string|null $group       Optional logical group.
     *
     * @return self Fluent interface.
     *
     * @throws SettingAlreadyExistsException If `$allowUpdate` is false and key exists.
     * @throws InvalidArgumentException      If the value type cannot be detected.
     */
    public function set(
        string $name,
        mixed $value,
        SettingType $type = SettingType::AUTO,
        bool $allowUpdate = true,
        ?string $description = null,
        ?string $group = null,
    ): self {
        if ($type === SettingType::AUTO) {
            $type = $this->detectType($value);
        }

        $setting = $this->get($name);

        if ($setting !== null && !$allowUpdate) {
            throw SettingAlreadyExistsException::forKey($name);
        }

        if ($setting !== null) {
            $setting->setType($type)->setValue($value);

            if ($description !== null) {
                $setting->setDescription($description);
            }
            if ($group !== null) {
                $setting->setGroup($group);
            }
        } else {
            $setting = new SettingEntity($name, $type, $value);

            if ($description !== null) {
                $setting->setDescription($description);
            }
            if ($group !== null) {
                $setting->setGroup($group);
            }

            $this->entityManager->persist($setting);
        }

        $this->entityManager->flush();
        $this->cache[$name] = $setting;

        return $this;
    }

    /**
     * Update an existing setting's value.
     *
     * @param string     $name       Setting key — must already exist.
     * @param mixed      $value      New value.
     * @param UpdateType $updateType Whether to re-detect or keep the existing type.
     *
     * @return SettingEntity The updated entity.
     *
     * @throws SettingNotFoundException      If the key does not exist.
     * @throws SettingTypeMismatchException If KEEP_CURRENT and the value type doesn't match.
     */
    public function update(string $name, mixed $value, UpdateType $updateType = UpdateType::OVERRIDE): SettingEntity
    {
        $setting = $this->get($name)
            ?? throw SettingNotFoundException::forKey($name, 'update');

        if ($updateType === UpdateType::OVERRIDE) {
            $setting->setType($this->detectType($value));
        }

        $this->validateType($setting->getType(), $value);
        $setting->setValue($value);

        $this->entityManager->flush();
        $this->cache[$name] = $setting;

        return $setting;
    }

    /**
     * Retrieve a setting entity by key.
     *
     * @return SettingEntity|null The entity, or `null` if not found.
     */
    public function get(string $key): ?SettingEntity
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $setting = $this->entityManager
            ->getRepository(SettingEntity::class)
            ->findOneBy(['name' => $key]);

        if ($setting !== null) {
            $this->cache[$key] = $setting;
        }

        return $setting;
    }

    /**
     * Get the typed value of a setting directly, or `null` if not found.
     *
     * Shorthand for `$manager->get($key)?->getValue()`.
     */
    public function getValue(string $key): mixed
    {
        return $this->get($key)?->getValue();
    }

    /**
     * Get the typed value of a setting, falling back to a default if the key doesn't exist.
     *
     * @template TDefault
     *
     * @param string   $key     Setting key.
     * @param TDefault $default Value returned when the key is not found.
     *
     * @return mixed|TDefault
     */
    public function getOrDefault(string $key, mixed $default = null): mixed
    {
        $setting = $this->get($key);

        return $setting !== null ? $setting->getValue() : $default;
    }

    /**
     * Check whether a setting key exists.
     *
     * Alias: {@see self::has()}.
     */
    public function exists(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Check whether a setting key exists.
     *
     * Alias for {@see self::exists()}.
     */
    public function has(string $key): bool
    {
        return $this->exists($key);
    }

    /**
     * Retrieve all settings.
     *
     * @return SettingEntity[] Indexed array of all setting entities.
     */
    public function all(): array
    {
        return $this->entityManager
            ->getRepository(SettingEntity::class)
            ->findAll();
    }

    /**
     * Remove a setting by key.
     *
     * @return self Fluent interface.
     *
     * @throws SettingNotFoundException If the key does not exist.
     */
    public function remove(string $key): self
    {
        $setting = $this->get($key)
            ?? throw SettingNotFoundException::forKey($key, 'remove');

        $this->entityManager->remove($setting);
        $this->entityManager->flush();
        unset($this->cache[$key]);

        return $this;
    }

    // -------------------------------------------------------------------------
    //  Bulk Operations
    // -------------------------------------------------------------------------

    /**
     * Set multiple settings at once.
     *
     * Each entry must have a `value` key and may optionally include `type`, `description`, and `group`.
     *
     * ```php
     * $manager->setMany([
     *     'site.name'  => ['value' => 'Acme', 'type' => SettingType::STRING, 'group' => 'site'],
     *     'cache.ttl'  => ['value' => 3600],
     *     'debug.mode' => ['value' => false, 'description' => 'Enable debug mode'],
     * ]);
     * ```
     *
     * @param array<string, array{value: mixed, type?: SettingType, description?: ?string, group?: ?string}> $settings
     *
     * @return self Fluent interface.
     */
    public function setMany(array $settings): self
    {
        foreach ($settings as $name => $data) {
            $this->set(
                name: $name,
                value: $data['value'],
                type: $data['type'] ?? SettingType::AUTO,
                description: $data['description'] ?? null,
                group: $data['group'] ?? null,
            );
        }

        return $this;
    }

    /**
     * Remove multiple settings by key.
     *
     * @param string[] $keys Keys to remove.
     *
     * @return self Fluent interface.
     *
     * @throws SettingNotFoundException If any key does not exist.
     */
    public function removeMany(array $keys): self
    {
        foreach ($keys as $key) {
            $this->remove($key);
        }

        return $this;
    }

    /**
     * Remove all settings from the database.
     *
     * @return self Fluent interface.
     */
    public function clear(): self
    {
        foreach ($this->all() as $setting) {
            $this->entityManager->remove($setting);
        }

        $this->entityManager->flush();
        $this->cache = [];

        return $this;
    }

    // -------------------------------------------------------------------------
    //  Search & Filter
    // -------------------------------------------------------------------------

    /**
     * Search for settings whose name contains the given substring (case-insensitive).
     *
     * @param string $pattern Substring to match against setting names.
     *
     * @return SettingEntity[] Matching settings.
     */
    public function search(string $pattern): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        /** @var SettingEntity[] */
        return $qb->select('s')
            ->from(SettingEntity::class, 's')
            ->where($qb->expr()->like('LOWER(s.name)', ':pattern'))
            ->setParameter('pattern', '%' . mb_strtolower($pattern) . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retrieve all settings belonging to a specific group.
     *
     * @return SettingEntity[] Settings in the given group.
     */
    public function findByGroup(string $group): array
    {
        return $this->entityManager
            ->getRepository(SettingEntity::class)
            ->findBy(['settingGroup' => $group]);
    }

    // -------------------------------------------------------------------------
    //  Counting
    // -------------------------------------------------------------------------

    /**
     * Returns the total number of persisted settings.
     *
     * Implements {@see Countable} — allows `count($manager)`.
     *
     * @return int<0, max>
     */
    public function count(): int
    {
        /** @var int<0, max> */
        return (int) $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(s.name)')
            ->from(SettingEntity::class, 's')
            ->getQuery()
            ->getSingleScalarResult();
    }

    // -------------------------------------------------------------------------
    //  Import / Export Convenience
    // -------------------------------------------------------------------------

    /**
     * Export all settings to an array.
     *
     * @return array<int, array{name: string, value: string, type: string, description: ?string, group: ?string, createdAt: string, updatedAt: ?string}>
     */
    public function export(): array
    {
        return SettingsExporter::toArray($this);
    }

    /**
     * Export all settings to a JSON string.
     */
    public function exportJson(): string
    {
        return SettingsExporter::toJson($this);
    }

    /**
     * Import settings from an array.
     *
     * @param array<mixed> $data
     *
     * @return self Fluent interface.
     */
    public function import(array $data): self
    {
        SettingsImporter::fromArray($this, $data);
        $this->cache = []; // bust cache after bulk import
        return $this;
    }

    /**
     * Import settings from a JSON string.
     *
     * @return self Fluent interface.
     */
    public function importJson(string $json): self
    {
        SettingsImporter::fromJson($this, $json);
        $this->cache = []; // bust cache after bulk import
        return $this;
    }

    // -------------------------------------------------------------------------
    //  Cache Management
    // -------------------------------------------------------------------------

    /**
     * Clear the in-memory cache, forcing subsequent reads to hit the database.
     *
     * Useful after external modifications to the settings table.
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    // -------------------------------------------------------------------------
    //  Internal Helpers
    // -------------------------------------------------------------------------

    /**
     * Seed default settings that don't already exist in the database.
     */
    private function initializeDefaults(): void
    {
        foreach ($this->defaultSettings as $name => $data) {
            if (!$this->exists($name)) {
                $this->set(
                    name: $name,
                    value: $data['value'],
                    type: $data['type'] ?? SettingType::AUTO,
                    allowUpdate: false,
                    description: $data['description'] ?? null,
                    group: $data['group'] ?? null,
                );
            }
        }
    }

    /**
     * Auto-detect the {@see SettingType} for a given value.
     *
     * @throws InvalidArgumentException If the type cannot be determined.
     */
    private function detectType(mixed $value): SettingType
    {
        return match (true) {
            $value instanceof SettingType      => $value,
            $value instanceof DateTimeImmutable => SettingType::DATETIME,
            is_bool($value)                     => SettingType::BOOLEAN,
            is_int($value)                      => SettingType::INTEGER,
            is_float($value)                    => SettingType::FLOAT,
            is_array($value)                    => SettingType::ARRAY,
            is_string($value) && json_validate($value) => SettingType::JSON,
            is_string($value)                   => SettingType::STRING,
            default => throw new InvalidArgumentException(
                'Unsupported value type for auto-detection: ' . get_debug_type($value),
            ),
        };
    }

    /**
     * Validate that a value is compatible with the expected {@see SettingType}.
     *
     * @throws SettingTypeMismatchException If the value doesn't match.
     */
    private function validateType(SettingType $expectedType, mixed $value): void
    {
        if ($expectedType === SettingType::AUTO) {
            return;
        }

        $isValid = match ($expectedType) {
            SettingType::STRING   => is_string($value),
            SettingType::INTEGER  => is_int($value),
            SettingType::BOOLEAN  => is_bool($value),
            SettingType::FLOAT    => is_float($value),
            SettingType::JSON     => is_string($value) && json_validate($value),
            SettingType::ARRAY    => is_array($value),
            SettingType::DATETIME => $value instanceof DateTimeImmutable,
        };

        if (!$isValid) {
            throw SettingTypeMismatchException::create($expectedType, get_debug_type($value));
        }
    }
}
