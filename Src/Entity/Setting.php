<?php declare(strict_types=1);

namespace Temant\SettingsManager\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Stringable;
use Temant\SettingsManager\Enum\SettingType;

#[ORM\Entity]
class Setting implements Stringable
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $type;  // Store SettingType as a string

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(string $name, SettingType $type, mixed $value = null)
    {
        $this->name = $name;
        $this->setType($type);
        $this->setValue($value);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

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

    public function setValue(mixed $value): self
    {
        $this->value = match ($this->getType()) {
            SettingType::STRING, SettingType::INTEGER, SettingType::BOOLEAN, SettingType::FLOAT => (string) $value,
            SettingType::JSON => json_encode($value),
        };
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getType(): SettingType
    {
        return SettingType::from($this->type); // Convert stored string back to SettingType enum
    }

    public function setType(SettingType $type): self
    {
        $this->type = $type->value; // Store SettingType enum as its string value
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @inheritDoc
     */
    public function __tostring(): string
    {
        return $this->value;
    }
}