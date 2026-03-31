<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Exception;

use RuntimeException;
use Throwable;

/**
 * Thrown when a settings import or export operation fails.
 */
final class SettingsImportExportException extends RuntimeException
{
    public function __construct(
        string $message = 'Settings import/export failed.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
