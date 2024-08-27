<?php declare(strict_types=1);

namespace Temant\SettingsManager\Enum {

    enum SettingType: string
    {
        case STRING = 'string';
        case INTEGER = 'integer';
        case BOOLEAN = 'boolean';
        case FLOAT = 'float';
        case ARRAY = 'array';
        case JSON = 'json';
    }
}