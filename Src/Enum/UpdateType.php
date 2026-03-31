<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Enum;

/**
 * Controls how the type of an existing setting is handled during an update.
 *
 * When calling {@see \Temant\SettingsManager\SettingsManager::update()}, this enum
 * determines whether the stored type is preserved or re-detected from the new value.
 */
enum UpdateType: string
{
    /**
     * Keep the current type — the new value must be compatible with the existing type.
     *
     * A {@see \Temant\SettingsManager\Exception\SettingTypeMismatchException} is thrown
     * if the new value does not match.
     */
    case KEEP_CURRENT = 'keep_current';

    /**
     * Override the current type by auto-detecting from the new value.
     *
     * This allows changing both the value and its type in a single operation.
     */
    case OVERRIDE = 'override';
}
