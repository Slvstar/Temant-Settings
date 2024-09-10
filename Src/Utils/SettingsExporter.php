<?php declare(strict_types=1);

namespace Temant\SettingsManager\Utils {

    use Temant\SettingsManager\Entity\SettingEntity;
    use Temant\SettingsManager\Exception\SettingsImportExportException;
    use Temant\SettingsManager\SettingsManager;
    use Throwable;

    final class SettingsExporter
    {
        /**
         * Exports all settings to an array format.
         *
         * @param SettingsManager $settingsManager The Doctrine entity manager.
         * @return SettingEntity[] The exported settings as an array.
         */
        public static function exportToArray(SettingsManager $settingsManager): array
        {
            return array_map(function (SettingEntity $setting): array {
                return $setting->__toArray();
            }, $settingsManager->all());
        }

        /**
         * Exports all settings to a JSON format.
         *
         * @param SettingsManager $settingsManager The Doctrine entity manager.
         * @return string The exported settings as a JSON string.
         * @throws SettingsImportExportException If the export fails.
         */
        public static function exportToJson(SettingsManager $settingsManager): string
        {
            try {
                $data = self::exportToArray($settingsManager);
                return json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            } catch (Throwable $e) {
                throw new SettingsImportExportException(
                    sprintf("Failed to export settings to JSON: %s", $e->getMessage())
                );
            }
        }
    }
}