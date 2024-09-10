<?php declare(strict_types=1);

namespace Temant\SettingsManager\Utils {

    use Doctrine\ORM\EntityManagerInterface;
    use Temant\SettingsManager\Entity\SettingEntity;
    use Temant\SettingsManager\Enum\SettingType;
    use Temant\SettingsManager\Exception\SettingsImportExportException;
    use Throwable;

    final class SettingsImporter
    {
        /**
         * Imports settings from an array.
         *
         * @param EntityManagerInterface $entityManager The Doctrine entity manager.
         * @param array $settingsData The settings data to import.
         * @return void
         * @throws SettingsImportExportException If import fails.
         */
        public static function importFromArray(EntityManagerInterface $entityManager, array $settingsData): void
        {
            try {
                foreach ($settingsData as $data) {
                    $setting = $entityManager->getRepository(SettingEntity::class)
                        ->findOneBy(['name' => $data['name']]) ?? new SettingEntity(
                        $data['name'],
                        SettingType::from($data['type']),
                        $data['value']
                    );

                    // Set the values if the setting already exists
                    $setting->setType(SettingType::from($data['type']));
                    $setting->setValue($data['value']);

                    $entityManager->persist($setting);
                }

                // Flush to save changes
                $entityManager->flush();
            } catch (Throwable $e) {
                throw new SettingsImportExportException("Failed to import settings from array: " . $e->getMessage());
            }
        }

        /**
         * Imports settings from a JSON string.
         *
         * @param EntityManagerInterface $entityManager The Doctrine entity manager.
         * @param string $jsonData The JSON data to import.
         * @return void
         * @throws SettingsImportExportException If import fails.
         */
        public static function importFromJson(EntityManagerInterface $entityManager, string $jsonData): void
        {
            try {
                $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
                self::importFromArray($entityManager, $data);
            } catch (Throwable $e) {
                throw new SettingsImportExportException("Failed to import settings from JSON: " . $e->getMessage());
            }
        }
    }
}