<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Exception;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when a setting's value does not match the expected type.
 */
final class SettingTypeMismatchException extends RuntimeException implements Throwable
{
    /**
     * Constructor for SettingTypeMismatchException.
     *
     * @param string $message The error message. Defaults to 'SettingEntity type mismatch.'.
     * @param int $code The error code. Defaults to 0.
     * @param Throwable|null $previous The previous throwable used for exception chaining.
     */
    public function __construct(string $message = 'SettingEntity type mismatch.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}