<?php declare(strict_types=1);

namespace Temant\SettingsManager\Exception {
    use RuntimeException;
    use Throwable;
    final class SettingNotFoundException extends RuntimeException implements Throwable
    {
    }
}