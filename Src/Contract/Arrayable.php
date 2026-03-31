<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Contract;

/**
 * Contract for objects that can be represented as an associative array.
 *
 * Implementing classes should return a self-contained, serializable array —
 * all values should be scalars, arrays, or null (no objects).
 *
 * @template TKey of string
 * @template TValue
 */
interface Arrayable
{
    /**
     * Convert the object to an associative array.
     *
     * @return array<TKey, TValue>
     */
    public function __toArray(): array;
}
