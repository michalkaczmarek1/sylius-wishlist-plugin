<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Entity\Wishlist;

use DateTimeInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;
use Sylius\Resource\Model\TimestampableTrait;

class WishlistProduct implements WishlistProductInterface
{
    use TimestampableTrait;

    private ?int $id = null;

    private ?WishlistInterface $wishlist = null;

    private ?ProductVariantInterface $productVariant = null;

    /** @var ?DateTimeInterface */
    protected $createdAt;

    /** @var ?DateTimeInterface */
    protected $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWishlist(): ?WishlistInterface
    {
        return $this->wishlist;
    }

    public function setWishlist(?WishlistInterface $wishlist): void
    {
        $this->wishlist = $wishlist;
    }

    public function getProductVariant(): ?ProductVariantInterface
    {
        return $this->productVariant;
    }

    public function setProductVariant(?ProductVariantInterface $productVariant): void
    {
        $this->productVariant = $productVariant;
    }
}
