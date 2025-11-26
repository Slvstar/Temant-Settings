<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Utils;

use Temant\SettingsManager\Enum\SettingType;
use Temant\SettingsManager\Exception\SettingsImportExportException;
use Temant\SettingsManager\SettingsManager;
use Throwable;

use function is_array;
use function sprintf;

final class SettingsImporter
{
    /**
     * Imports settings from an array.
     *
     * @param SettingsManager $settingsManager The settings manager.
     * @param array<array{name: string, value: mixed, type: string}> $settingsData The settings data to import.
     * @return void
     * @throws SettingsImportExportException If import fails.
     */
    public static function fromArray(SettingsManager $settingsManager, array $settingsData): void
    {
        try {
            foreach ($settingsData as $data) {
                $settingsManager->set($data['name'], $data['value'], SettingType::from($data['type']));
            }
        } catch (Throwable $e) {
            throw new SettingsImportExportException(
                sprintf("Failed to import settings from array: %s", $e->getMessage())
            );
        }
    }

    /**
     * Imports settings from a JSON string.
     *
     * @param SettingsManager $settingsManager The settings manager.
     * @param string $jsonData The JSON data to import.
     * @return void
     * @throws SettingsImportExportException If the import fails.
     */
    public static function fromJson(SettingsManager $settingsManager, string $jsonData): void
    {
        try {
            /**
             * @var null|false|array<array{name: string, value: mixed, type: string}>
             */
            $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                throw new SettingsImportExportException('Invalid JSON format, array expected.');
            }

            self::fromArray($settingsManager, $data);
        } catch (Throwable $e) {
            throw new SettingsImportExportException(
                sprintf("Failed to import settings from JSON: %s", $e->getMessage())
            );
        }
    }
}