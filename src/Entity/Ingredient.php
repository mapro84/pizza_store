<?php

namespace App\Entity;

use App\Repository\IngredientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IngredientRepository::class)]
#[ORM\Table(name: 'ingredient')]
class Ingredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: 'decimal', precision: 6, scale: 2)]
    private ?string $price = null;

    #[ORM\Column]
    private bool $isAllergen = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $allergenType = null;

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function isAllergen(): bool
    {
        return $this->isAllergen;
    }

    public function setIsAllergen(bool $isAllergen): static
    {
        $this->isAllergen = $isAllergen;
        return $this;
    }

    public function getAllergenType(): ?string
    {
        return $this->allergenType;
    }

    public function setAllergenType(?string $allergenType): static
    {
        $this->allergenType = $allergenType;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getFormattedPrice(): string
    {
        return number_format((float)$this->price, 2, ',', ' ') . ' €';
    }
}
