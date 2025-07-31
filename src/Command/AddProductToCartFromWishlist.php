<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Command;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Order\Model\OrderInterface;

final readonly class AddProductToCartFromWishlist
{
    public function __construct(
        private OrderInterface $cart,
        private ProductVariantInterface $productVariant,
        private int $quantity,
    ) {
    }

    public function getCart(): OrderInterface
    {
        return $this->cart;
    }

    public function getProductVariant(): ProductVariantInterface
    {
        return $this->productVariant;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
