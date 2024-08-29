<?php declare(strict_types=1);

namespace Temant\SettingsManager\Exception {

    use RuntimeException;
    use Throwable;

    /**
     * Exception thrown when an attempt is made to create a setting that already exists.
     */
    final class SettingAlreadyExistsException extends RuntimeException implements Throwable
    {
        /**
         * Constructor for SettingAlreadyExistsException.
         *
         * @param string $message The error message. Defaults to 'Setting already exists.'.
         * @param int $code The error code. Defaults to 0.
         * @param Throwable|null $previous The previous throwable used for the exception chaining.
         */
        public function __construct(string $message = 'Setting already exists.', int $code = 0, ?Throwable $previous = null)
        {
            parent::__construct($message, $code, $previous);
        }
    }
}