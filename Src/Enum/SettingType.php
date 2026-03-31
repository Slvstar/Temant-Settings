<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Enum;

/**
 * Defines the supported data types for settings.
 *
 * Each case maps to a storage strategy: values are persisted as strings in the database
 * and cast back to their native PHP type on retrieval via {@see \Temant\SettingsManager\Entity\SettingEntity::getValue()}.
 */
enum SettingType: string
{
    /** Plain text value — stored and returned as-is. */
    case STRING = 'string';

    /** Whole number — cast to `int` on retrieval. */
    case INTEGER = 'integer';

    /** True/false — stored as `'true'`/`'false'`, returned as `bool`. */
    case BOOLEAN = 'boolean';

    /** Decimal number — cast to `float` on retrieval. */
    case FLOAT = 'float';

    /** JSON string — decoded to an associative array on retrieval. */
    case JSON = 'json';

    /** Native PHP array — JSON-encoded for storage, decoded on retrieval. */
    case ARRAY = 'array';

    /** ISO 8601 datetime — stored as string, returned as {@see \DateTimeImmutable}. */
    case DATETIME = 'datetime';

    /**
     * Sentinel type: the manager will auto-detect the real type from the value.
     *
     * Never persisted — always resolved to a concrete type before storage.
     */
    case AUTO = 'auto';

    /**
     * Checks whether this type is a concrete (persistable) type.
     *
     * @return bool True if this type can be stored directly, false for {@see self::AUTO}.
     */
    public function isConcrete(): bool
    {
        return $this !== self::AUTO;
    }
}
