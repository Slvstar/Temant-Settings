<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Enum;

/**
 * Enum SettingType defines the possible types for a setting in the settings manager.
 */
enum SettingType: string
{
    /**
     * Represents a string type setting.
     */
    case STRING = 'string';

    /**
     * Represents an integer type setting.
     */
    case INTEGER = 'integer';

    /**
     * Represents a boolean type setting.
     */
    case BOOLEAN = 'boolean';

    /**
     * Represents a float type setting.
     */
    case FLOAT = 'float';

    /**
     * Represents a JSON type setting.
     */
    case JSON = 'json';

    /**
     * Represents an auto-determined type setting.
     */
    case AUTO = 'auto';
}