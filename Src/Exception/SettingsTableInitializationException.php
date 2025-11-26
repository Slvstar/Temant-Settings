<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Exception;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when the settings table initialization fails.
 */
class SettingsTableInitializationException extends RuntimeException implements Throwable
{
    /**
     * Constructor for SettingsTableInitializationException.
     *
     * @param string $message The error message.
     * @param int $code The error code.
     * @param Throwable|null $previous The previous throwable for exception chaining.
     */
    public function __construct(string $message = "Failed to initialize the settings table.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}