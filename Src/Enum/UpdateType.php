<?php declare(strict_types=1);

namespace Temant\SettingsManager\Enum {
    /**
     * Enum UpdateType defines the strategies for updating the type of a setting.
     */
    enum UpdateType: string
    {
        /**
         * Keep the current type of the setting without overriding it.
         * This strategy will retain the existing type of the setting even if a new type is provided.
         */
        case KEEP_CURRENT = 'keep_current';

        /**
         * Override the current type of the setting with the new type.
         * This strategy will replace the existing type of the setting with the new type provided.
         */
        case OVERRIDE = 'override';
    }
}