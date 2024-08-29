<?php declare(strict_types=1);

namespace Temant\SettingsManager\Exception {
    use RuntimeException;
    use Throwable;
    final class SettingTypeMismatchException extends RuntimeException implements Throwable
    {
    }
}