<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Contract;

/**
 * Interface Arrayable
 * A contract for classes that can be converted to an array.
 */
interface Arrayable
{
    /**
     * Converts the object to an associative array.
     *
     * @return array<mixed> The array representation of the object.
     */
    public function __toArray(): array;
}