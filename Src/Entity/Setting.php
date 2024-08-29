<?php declare(strict_types=1);

namespace Temant\SettingsManager\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Stringable;
use Temant\SettingsManager\Enum\SettingType;
use Temant\SettingsManager\Exception\SettingTypeMismatchException;
use DateTimeImmutable;

#[ORM\Entity]
class Setting implements Stringable
{
    /**
     * The name of the setting, which is the primary key.
     * 
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $name;

    /**
     * The value of the setting.
     * 
     * @var string|null
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    /**
     * The type of the setting (e.g., string, integer, boolean).
     * 
     * @var string
     */
    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $type;

    /**
     * The date and time when the setting was created.
     * 
     * @var DateTimeImmutable
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    /**
     * The date and time when the setting was last updated.
     * 
     * @var DateTimeImmutable|null
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * Setting constructor.
     * 
     * @param string $name The name of the setting.
     * @param SettingType $type The type of the setting.
     * @param mixed $value The value of the setting.
     */
    public function __construct(string $name, SettingType $type, mixed $value = null)
    {
        $this->name = $name;
        $this->setType($type);
        $this->setValue($value);
        $this->createdAt = new DateTimeImmutable;
    }

    /**
     * Gets the name of the setting.
     * 
     * @return string The name of the setting.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name of the setting.
     * 
     * @param string $name The new name of the setting.
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        $this->updatedAt = new DateTimeImmutable;
        return $this;
    }

    /**
     * Gets the value of the setting.
     * 
     * @return mixed The value of the setting in its correct type.
     */
    public function getValue(): mixed
    {
        return match ($this->getType()) {
            SettingType::STRING => $this->value,
            SettingType::INTEGER => (int) $this->value,
            SettingType::BOOLEAN => (bool) $this->value,
            SettingType::FLOAT => (float) $this->value,
            SettingType::JSON => json_decode($this->value, true),
        };
    }

    /**
     * Sets the value of the setting.
     * 
     * @param mixed $value The new value of the setting.
     * @return self
     * @throws SettingTypeMismatchException if the value does not match the expected type.
     */
    public function setValue(mixed $value): self
    {
        $expectedType = $this->getType();
        $this->validateType($expectedType, $value);

        $this->value = match ($expectedType) {
            SettingType::STRING, SettingType::INTEGER, SettingType::BOOLEAN, SettingType::FLOAT => (string) $value,
            SettingType::JSON => json_encode($value),
        };
        $this->updatedAt = new DateTimeImmutable;
        return $this;
    }

    /**
     * Gets the type of the setting.
     * 
     * @return SettingType The type of the setting.
     */
    public function getType(): SettingType
    {
        return SettingType::from($this->type);
    }

    /**
     * Sets the type of the setting.
     * 
     * @param SettingType $type The new type of the setting.
     * @return self
     */
    public function setType(SettingType $type): self
    {
        $this->type = $type->value;
        $this->updatedAt = new DateTimeImmutable;
        return $this;
    }

    /**
     * Gets the creation timestamp of the setting.
     * 
     * @return DateTimeImmutable The creation timestamp of the setting.
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Gets the last update timestamp of the setting.
     * 
     * @return DateTimeImmutable|null The last update timestamp of the setting, or null if it has never been updated.
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Converts the setting to a string, returning the value.
     * 
     * @return string The value of the setting as a string.
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Validates that the given value matches the expected SettingType.
     *
     * @param SettingType $expectedType The expected type of the value.
     * @param mixed $value The value to validate.
     * @throws SettingTypeMismatchException if the value does not match the expected type.
     */
    private function validateType(SettingType $expectedType, mixed $value): void
    {
        $isValid = match ($expectedType) {
            SettingType::STRING => is_string($value),
            SettingType::INTEGER => is_int($value),
            SettingType::BOOLEAN => is_bool($value),
            SettingType::FLOAT => is_float($value),
            SettingType::JSON => is_array($value) || is_object($value),
        };

        if (!$isValid) {
            throw new SettingTypeMismatchException("Expected type '{$expectedType->value}' but got '{gettype($value)'");
        }
    }
}