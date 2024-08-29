<?php declare(strict_types=1);

namespace Temant\SettingsManager\Exception {
    use RuntimeException;
    use Throwable;
    final class SettingAlreadyExistsException extends RuntimeException implements Throwable
    {
    }
}