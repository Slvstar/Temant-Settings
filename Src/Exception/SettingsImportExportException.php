<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Exception;

use RuntimeException;
use Throwable;

/**
 * Class SettingsImportExportException
 * Custom exception class for settings import/export errors.
 */
class SettingsImportExportException extends RuntimeException implements Throwable
{
}