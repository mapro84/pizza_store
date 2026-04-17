<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\Table(name: 'customer')]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: true)]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(name: 'zip_code', length: 10, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(name: 'loyalty_points', nullable: true)]
    private ?int $loyaltyPoints = 0;

    #[ORM\Column(name: 'is_vip', nullable: true)]
    private ?bool $isVip = false;

    #[ORM\Column(name: 'favorite_payment_method', length: 50, nullable: true)]
    private ?string $favoritePaymentMethod = null;

    public function __construct()
    {
        $this->loyaltyPoints = 0;
        $this->isVip = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->user?->getFirstName();
    }

    public function setFirstName(string $firstName): static
    {
        if ($this->user) {
            $this->user->setFirstName($firstName);
        }
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->user?->getLastName();
    }

    public function setLastName(string $lastName): static
    {
        if ($this->user) {
            $this->user->setLastName($lastName);
        }
        return $this;
    }

    public function getFullName(): string
    {
        return trim(($this->getFirstName() ?? '') . ' ' . ($this->getLastName() ?? ''));
    }

    public function getEmail(): ?string
    {
        return $this->user?->getEmail();
    }

    public function setEmail(string $email): static
    {
        if ($this->user) {
            $this->user->setEmail($email);
        }
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->user?->getPhone();
    }

    public function setPhone(?string $phone): static
    {
        if ($this->user) {
            $this->user->setPhone($phone);
        }
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getFullAddress(): string
    {
        $parts = array_filter([$this->address, $this->postalCode, $this->city]);
        return implode(', ', $parts);
    }

    public function getLoyaltyPoints(): ?int
    {
        return $this->loyaltyPoints ?? 0;
    }

    public function setLoyaltyPoints(int $loyaltyPoints): static
    {
        $this->loyaltyPoints = $loyaltyPoints;
        return $this;
    }

    public function addLoyaltyPoints(int $points): static
    {
        $this->loyaltyPoints = ($this->loyaltyPoints ?? 0) + $points;
        return $this;
    }

    public function isVip(): ?bool
    {
        return $this->isVip;
    }

    public function setIsVip(bool $isVip): static
    {
        $this->isVip = $isVip;
        return $this;
    }

    public function getFavoritePaymentMethod(): ?string
    {
        return $this->favoritePaymentMethod;
    }

    public function setFavoritePaymentMethod(?string $favoritePaymentMethod): static
    {
        $this->favoritePaymentMethod = $favoritePaymentMethod;
        return $this;
    }

    public function getFavoritePaymentMethodLabel(): string
    {
        return match($this->favoritePaymentMethod) {
            'carte' => 'Carte bancaire',
            'especes' => 'Espèces',
            'paypal' => 'PayPal',
            'swish' => 'Swish',
            default => $this->favoritePaymentMethod ?? '—',
        };
    }
}
