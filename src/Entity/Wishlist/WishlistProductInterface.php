<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Entity\Wishlist;

use Sylius\Component\Product\Model\ProductVariantInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TimestampableInterface;

interface WishlistProductInterface extends TimestampableInterface, ResourceInterface
{
    public function getWishlist(): ?WishlistInterface;

    public function setWishlist(?WishlistInterface $wishlist): void;

    public function getProductVariant(): ?ProductVariantInterface;

    public function setProductVariant(?ProductVariantInterface $productVariant): void;
}
