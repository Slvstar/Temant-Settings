<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Utils;

use Temant\SettingsManager\Entity\SettingEntity;
use Temant\SettingsManager\Exception\SettingsImportExportException;
use Temant\SettingsManager\SettingsManager;

/**
 * Exports all settings from a {@see SettingsManager} to portable formats.
 *
 * Exported data uses the structure produced by {@see SettingEntity::__toArray()},
 * which contains only scalars and can be safely serialized or transferred.
 */
final class SettingsExporter
{
    /**
     * Export all settings as an array of associative arrays.
     *
     * @param SettingsManager $settingsManager The manager to export from.
     *
     * @return array<int, array{name: string, value: string, type: string, description: ?string, group: ?string, createdAt: string, updatedAt: ?string}>
     */
    public static function toArray(SettingsManager $settingsManager): array
    {
        return array_values(array_map(
            static fn(SettingEntity $setting): array => $setting->__toArray(),
            $settingsManager->all(),
        ));
    }

    /**
     * Export all settings as a pretty-printed JSON string.
     *
     * @param SettingsManager $settingsManager The manager to export from.
     *
     * @return string JSON-encoded settings.
     *
     * @throws SettingsImportExportException If JSON encoding fails.
     */
    public static function toJson(SettingsManager $settingsManager): string
    {
        $data = self::toArray($settingsManager);

        return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
