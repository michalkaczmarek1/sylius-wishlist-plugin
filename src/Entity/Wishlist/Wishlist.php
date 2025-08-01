<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Entity\Wishlist;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Resource\Model\TimestampableTrait;

class Wishlist implements WishlistInterface
{
    use TimestampableTrait;

    private ?int $id = null;

    private ?string $wishlistToken = null;

    private ?ShopUserInterface $customer = null;

    /** @var Collection<int, WishlistProductInterface|null> */
    private Collection $wishlistProducts;

    /** @var ?DateTimeInterface */
    protected $createdAt;

    /** @var ?DateTimeInterface */
    protected $updatedAt;

    public function __construct()
    {
        $this->wishlistProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWishlistToken(): ?string
    {
        return $this->wishlistToken;
    }

    public function setWishlistToken(?string $wishlistToken): void
    {
        $this->wishlistToken = $wishlistToken;
    }

    /**
     * @return Collection<int, WishlistProductInterface|null>
     */
    public function getWishlistProducts(): Collection
    {
        return $this->wishlistProducts;
    }

    public function addWishlistProduct(?WishlistProductInterface $wishlistProduct): void
    {
        /** @var WishlistProduct $wishlistProductOriginal */
        foreach ($this->wishlistProducts as $wishlistProductOriginal) {
            if ($wishlistProductOriginal->getProductVariant()?->getId() === $wishlistProduct?->getProductVariant()?->getId()) {
                return;
            }
        }

        $this->wishlistProducts->add($wishlistProduct);
        $wishlistProduct?->setWishlist($this);
    }

    public function removeWishlistProduct(?WishlistProduct $wishlistProduct): void
    {
        if ($this->wishlistProducts->contains($wishlistProduct)) {
            $this->wishlistProducts->removeElement($wishlistProduct);
            $wishlistProduct?->setWishlist(null);
        }
    }

    public function getCustomer(): ?ShopUserInterface
    {
        return $this->customer;
    }

    public function setCustomer(?ShopUserInterface $customer): void
    {
        $this->customer = $customer;
    }
}
