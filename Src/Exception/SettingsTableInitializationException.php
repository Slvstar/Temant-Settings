<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Exception;

use RuntimeException;
use Throwable;

/**
 * Thrown when the database table for settings cannot be created or verified.
 */
final class SettingsTableInitializationException extends RuntimeException
{
    public function __construct(
        string $message = 'Failed to initialize the settings table.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
