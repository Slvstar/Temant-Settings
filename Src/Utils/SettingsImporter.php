<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Utils;

use Temant\SettingsManager\Enum\SettingType;
use Temant\SettingsManager\Exception\SettingsImportExportException;
use Temant\SettingsManager\SettingsManager;
use Throwable;

/**
 * Imports settings into a {@see SettingsManager} from portable formats.
 *
 * Each entry in the input must contain at least `name`, `value`, and `type` keys.
 * Optional keys (`description`, `group`) are applied when present.
 */
final class SettingsImporter
{
    /**
     * Import settings from an array of associative arrays.
     *
     * @param SettingsManager $settingsManager Target manager.
     * @param array<mixed> $settingsData Settings to import.
     *
     * @throws SettingsImportExportException If any entry fails validation or persistence.
     */
    public static function fromArray(SettingsManager $settingsManager, array $settingsData): void
    {
        try {
            foreach ($settingsData as $index => $data) {
                if (!is_array($data) || !isset($data['name'], $data['value'], $data['type'])) {
                    throw new SettingsImportExportException(
                        "Entry at index $index is missing required keys (name, value, type).",
                    );
                }

                /** @var string $name */
                $name = $data['name'];
                /** @var string $type */
                $type = $data['type'];
                /** @var ?string $description */
                $description = $data['description'] ?? null;
                /** @var ?string $group */
                $group = $data['group'] ?? null;

                $settingsManager->set(
                    name: $name,
                    value: $data['value'],
                    type: SettingType::from($type),
                    allowUpdate: true,
                    description: $description,
                    group: $group,
                );
            }
        } catch (SettingsImportExportException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SettingsImportExportException(
                "Failed to import settings from array: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    /**
     * Import settings from a JSON string.
     *
     * The JSON must decode to an array of objects/arrays — see {@see self::fromArray()} for
     * the expected structure of each entry.
     *
     * @param SettingsManager $settingsManager Target manager.
     * @param string          $jsonData        JSON-encoded settings.
     *
     * @throws SettingsImportExportException If the JSON is invalid or import fails.
     */
    public static function fromJson(SettingsManager $settingsManager, string $jsonData): void
    {
        try {
            /** @var mixed $data */
            $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                throw new SettingsImportExportException('Invalid JSON format, array expected.');
            }

            self::fromArray($settingsManager, $data);
        } catch (SettingsImportExportException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new SettingsImportExportException(
                "Failed to import settings from JSON: {$e->getMessage()}",
                previous: $e,
            );
        }
    }
}
