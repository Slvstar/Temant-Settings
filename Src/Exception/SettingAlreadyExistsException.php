<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Exception;

use RuntimeException;
use Throwable;

/**
 * Thrown when attempting to create a setting that already exists
 * while updates are explicitly disallowed.
 */
final class SettingAlreadyExistsException extends RuntimeException
{
    public function __construct(
        string $message = 'Setting already exists.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Named constructor for convenience.
     */
    public static function forKey(string $key): self
    {
        return new self("A setting with the name '$key' already exists.");
    }
}
