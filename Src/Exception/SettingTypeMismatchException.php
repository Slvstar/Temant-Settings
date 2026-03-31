<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Exception;

use RuntimeException;
use Temant\SettingsManager\Enum\SettingType;
use Throwable;

/**
 * Thrown when a value does not match the expected {@see SettingType}.
 */
final class SettingTypeMismatchException extends RuntimeException
{
    public function __construct(
        string $message = 'Setting type mismatch.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Named constructor with structured context.
     */
    public static function create(SettingType $expected, string $actual): self
    {
        return new self("Expected type {$expected->value} but got {$actual}.");
    }
}
