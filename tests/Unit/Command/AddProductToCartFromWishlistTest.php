<?php

declare(strict_types=1);

namespace Tests\SyliusAcademy\WishlistPlugin\Unit\Command;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Order\Model\OrderInterface;
use SyliusAcademy\WishlistPlugin\Command\AddProductToCartFromWishlist;

final class AddProductToCartFromWishlistTest extends TestCase
{
    public function test_it_exposes_data_properly(): void
    {
        $cart = self::createMock(OrderInterface::class);
        $productVariant = self::createMock(ProductVariantInterface::class);
        $quantity = 3;

        $command = new AddProductToCartFromWishlist($cart, $productVariant, $quantity);

        $this->assertSame($cart, $command->getCart());
        $this->assertSame($productVariant, $command->getProductVariant());
        $this->assertSame($quantity, $command->getQuantity());
    }
}
