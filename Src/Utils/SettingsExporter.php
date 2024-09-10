<?php declare(strict_types=1);

namespace Temant\SettingsManager\Utils {

    use Temant\SettingsManager\Entity\SettingEntity;
    use Temant\SettingsManager\SettingsManager;

    final class SettingsExporter
    {
        /**
         * Exports all settings to an array format.
         *
         * @param SettingsManager $settingsManager The Doctrine entity manager.
         * @return array<mixed> The exported settings as an array.
         */
        public static function toArray(SettingsManager $settingsManager): array
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
         */
        public static function toJson(SettingsManager $settingsManager): string
        {
            $data = self::toArray($settingsManager);
            $json = json_encode($data, JSON_PRETTY_PRINT);
            return $json !== false ? $json : '';
        }
    }
}