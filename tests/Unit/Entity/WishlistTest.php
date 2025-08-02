<?php

declare(strict_types=1);

namespace Tests\SyliusAcademy\WishlistPlugin\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Product\Model\ProductVariantInterface;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\Wishlist;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistProduct;

final class WishlistTest extends TestCase
{
    private Wishlist $wishlist;

    protected function setUp(): void
    {
        $this->wishlist = new Wishlist();
    }

    public function test_adds_product_to_wishlist(): void
    {
        $wishlistProduct = $this->createWishlistProduct();

        $this->wishlist->addWishlistProduct($wishlistProduct);

        $this->assertWishlistContains($wishlistProduct);
    }

    public function test_does_not_add_duplicate_product(): void
    {
        $wishlistProduct1 = $this->createWishlistProduct();
        $wishlistProduct2 = $this->createWishlistProduct();

        $this->wishlist->addWishlistProduct($wishlistProduct1);
        $this->wishlist->addWishlistProduct($wishlistProduct2);

        $wishlistProducts = $this->wishlist->getWishlistProducts();

        $this->assertCount(1, $wishlistProducts);
        $this->assertWishlistContains($wishlistProduct1);
    }

    public function test_removes_product_from_wishlist(): void
    {
        $wishlistProduct = $this->createWishlistProduct();

        $this->wishlist->addWishlistProduct($wishlistProduct);
        $this->wishlist->removeWishlistProduct($wishlistProduct);

        $this->assertWishlistDoesNotContain($wishlistProduct);
    }

    public function test_removing_nonexistent_product_does_nothing(): void
    {
        $wishlistProduct = $this->createWishlistProduct();

        $this->wishlist->removeWishlistProduct($wishlistProduct);

        $this->assertWishlistDoesNotContain($wishlistProduct);
    }

    private function createWishlistProduct(): WishlistProduct
    {
        $productVariant = $this->createMock(ProductVariantInterface::class);
        $wishlistProduct = new WishlistProduct();
        $wishlistProduct->setProductVariant($productVariant);

        return $wishlistProduct;
    }

    private function assertWishlistContains(WishlistProduct $wishlistProduct): void
    {
        $this->assertTrue($this->wishlist->getWishlistProducts()->contains($wishlistProduct));
    }

    private function assertWishlistDoesNotContain(WishlistProduct $wishlistProduct): void
    {
        $this->assertFalse($this->wishlist->getWishlistProducts()->contains($wishlistProduct));
    }
}
