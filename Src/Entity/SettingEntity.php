<?php

declare(strict_types=1);

namespace Temant\SettingsManager\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Stringable;
use Temant\SettingsManager\Contract\Arrayable;
use Temant\SettingsManager\Enum\SettingType;

/**
 * Represents a single persisted setting as a Doctrine ORM entity.
 *
 * Values are stored as strings in the database and cast back to their native
 * PHP type on retrieval based on the {@see SettingType} discriminator.
 *
 * @implements Arrayable<string, mixed>
 */
#[Entity]
class SettingEntity implements Stringable, Arrayable
{
    /** Unique setting key — acts as the primary key. */
    #[Id]
    #[Column(type: Types::STRING, length: 255, unique: true)]
    private string $name;

    /** Serialized value stored as text. */
    #[Column(type: Types::TEXT)]
    private string $value;

    /** Type discriminator used for casting on retrieval. */
    #[Column(type: Types::STRING, length: 50)]
    private string $type;

    /** Optional human-readable description of what this setting controls. */
    #[Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $description = null;

    /** Optional logical group for organizing related settings. */
    #[Column(name: "setting_group", type: Types::STRING, length: 255, nullable: true)]
    private ?string $settingGroup = null;

    /** Timestamp of initial creation — never changes after construction. */
    #[Column(name: "created_at", type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    /** Timestamp of the most recent modification, null until first update. */
    #[Column(name: "updated_at", type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * @param string      $name  Unique setting key.
     * @param SettingType $type  Data type for storage and retrieval casting.
     * @param mixed       $value Initial value (will be serialized to string).
     */
    public function __construct(string $name, SettingType $type, mixed $value = null)
    {
        $this->name = $name;
        $this->setType($type);
        $this->setValue($value);
        $this->createdAt = new DateTimeImmutable();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->touch();
        return $this;
    }

    /**
     * Returns the value cast to its native PHP type based on the stored {@see SettingType}.
     *
     * | SettingType | PHP return type          |
     * |------------|--------------------------|
     * | STRING     | `string`                 |
     * | INTEGER    | `int`                    |
     * | BOOLEAN    | `bool`                   |
     * | FLOAT      | `float`                  |
     * | JSON       | `array` (assoc)          |
     * | ARRAY      | `array`                  |
     * | DATETIME   | `DateTimeImmutable`       |
     *
     * @return mixed The value in its native PHP type.
     */
    public function getValue(): mixed
    {
        return match ($this->getType()) {
            SettingType::STRING   => $this->value,
            SettingType::INTEGER  => (int) $this->value,
            SettingType::BOOLEAN  => $this->value === 'true',
            SettingType::FLOAT    => (float) $this->value,
            SettingType::JSON     => json_decode($this->value, true),
            SettingType::ARRAY    => json_decode($this->value, true),
            SettingType::DATETIME => new DateTimeImmutable($this->value),
            default               => $this->value,
        };
    }

    /**
     * Returns the raw string value as stored in the database, without type casting.
     */
    public function getRawValue(): string
    {
        return $this->value;
    }

    /**
     * Sets the value, serializing it to a string for database storage.
     *
     * - Booleans are stored as `'true'` / `'false'`.
     * - Arrays are JSON-encoded.
     * - DateTimeImmutable is stored in ISO 8601 format.
     * - Scalars are cast to string.
     *
     * @param mixed $value The value to store.
     * @return self Fluent interface.
     */
    public function setValue(mixed $value): self
    {
        $this->value = match (true) {
            $value instanceof DateTimeImmutable => $value->format(DateTimeImmutable::ATOM),
            is_bool($value)                     => $value ? 'true' : 'false',
            is_array($value)                    => (string) json_encode($value, JSON_THROW_ON_ERROR),
            default                             => is_scalar($value) ? (string) $value : '',
        };

        $this->touch();
        return $this;
    }

    public function getType(): SettingType
    {
        return SettingType::from($this->type);
    }

    public function setType(SettingType $type): self
    {
        $this->type = $type->value;
        $this->touch();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->touch();
        return $this;
    }

    public function getGroup(): ?string
    {
        return $this->settingGroup;
    }

    public function setGroup(?string $group): self
    {
        $this->settingGroup = $group;
        $this->touch();
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Returns the raw stored value as a string.
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Converts the entity to a fully serializable associative array.
     *
     * Dates are formatted as ISO 8601 strings; all other values are scalars or arrays.
     *
     * @return array{name: string, value: string, type: string, description: ?string, group: ?string, createdAt: string, updatedAt: ?string}
     */
    public function __toArray(): array
    {
        return [
            'name'        => $this->name,
            'value'       => $this->value,
            'type'        => $this->type,
            'description' => $this->description,
            'group'       => $this->settingGroup,
            'createdAt'   => $this->createdAt->format(DateTimeImmutable::ATOM),
            'updatedAt'   => $this->updatedAt?->format(DateTimeImmutable::ATOM),
        ];
    }

    /**
     * Updates the modification timestamp.
     */
    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
