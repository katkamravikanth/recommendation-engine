<?php

namespace App\Entity;

use App\Entity\User;
use App\Entity\Product;
use App\Repository\PurchaseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PurchaseRepository::class)]
#[ORM\Table(name: "purchases")]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Purchase
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['purchase', 'user'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'purchases')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['purchase'])]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'purchases')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['purchase', 'user'])]
    private ?Product $product = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank(message: 'The quantity should not be blank.')]
    #[Assert\Positive(message: 'The quantity must be a positive number.')]
    #[Groups(['purchase', 'user'])]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['purchase', 'user'])]
    private ?\DateTimeInterface $purchaseDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPurchaseDate(): ?\DateTimeInterface
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(\DateTimeInterface $purchaseDate): self
    {
        $this->purchaseDate = $purchaseDate;

        return $this;
    }
}