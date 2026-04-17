<?php

namespace App\Entity;

use App\Repository\ProviderOrderItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProviderOrderItemRepository::class)]
#[ORM\Table(name: 'provider_order_item')]
class ProviderOrderItem
{
    public const TYPE_INGREDIENT = 'ingredient';
    public const TYPE_MATERIAL = 'material';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProviderOrder::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ProviderOrder $providerOrder = null;

    #[ORM\Column(length: 20)]
    private string $itemType = self::TYPE_INGREDIENT;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $unitPrice = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $quantity = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $deliveredQuantity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProviderOrder(): ?ProviderOrder
    {
        return $this->providerOrder;
    }

    public function setProviderOrder(?ProviderOrder $providerOrder): static
    {
        $this->providerOrder = $providerOrder;
        return $this;
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function setItemType(string $itemType): static
    {
        $this->itemType = $itemType;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;
        return $this;
    }

    public function getDeliveredQuantity(): ?string
    {
        return $this->deliveredQuantity;
    }

    public function setDeliveredQuantity(?string $deliveredQuantity): static
    {
        $this->deliveredQuantity = $deliveredQuantity;
        return $this;
    }

    public function getTotalPrice(): float
    {
        return (float)$this->unitPrice * (float)$this->quantity;
    }

    public function getFormattedTotalPrice(): string
    {
        return number_format($this->getTotalPrice(), 2, ',', ' ') . ' €';
    }
}
