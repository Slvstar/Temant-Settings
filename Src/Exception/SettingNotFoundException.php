<?php declare(strict_types=1);

namespace Temant\SettingsManager\Exception {

    use RuntimeException;
    use Throwable;

    /**
     * Exception thrown when a requested setting is not found in the settings storage.
     */
    final class SettingNotFoundException extends RuntimeException implements Throwable
    {
        /**
         * Constructor for SettingNotFoundException.
         *
         * @param string $message The error message. Defaults to 'Setting not found.'.
         * @param int $code The error code. Defaults to 0.
         * @param Throwable|null $previous The previous throwable used for the exception chaining.
         */
        public function __construct(string $message = 'Setting not found.', int $code = 0, ?Throwable $previous = null)
        {
            parent::__construct($message, $code, $previous);
        }
    }
}