<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Exception;

use RuntimeException;
use Throwable;

/**
 * Thrown when a requested setting key does not exist in the database.
 */
final class SettingNotFoundException extends RuntimeException
{
    public function __construct(
        string $message = 'Setting not found.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Named constructor for a missing key during a specific operation.
     */
    public static function forKey(string $key, string $operation = 'access'): self
    {
        return new self("Cannot $operation. No setting found with the key '$key'.");
    }
}
