<?php

declare(strict_types=1);

namespace Tests\SyliusAcademy\WishlistPlugin\Unit\Twig\Component;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistInterface;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistProduct;
use SyliusAcademy\WishlistPlugin\Provider\WishlistProviderInterface;
use SyliusAcademy\WishlistPlugin\Provider\WishlistTokenProviderInterface;
use SyliusAcademy\WishlistPlugin\Twig\Component\WishlistComponent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

class WishlistComponentTest extends TestCase
{
    private WishlistComponent $component;

    private RequestStack $requestStack;

    private WishlistProviderInterface $wishlistProvider;

    private RepositoryInterface $wishlistProductRepository;

    private FactoryInterface $wishlistProductFactory;

    private EntityManagerInterface $entityManager;

    private ProductVariantRepositoryInterface $productVariantRepository;

    private ProductVariantResolverInterface $productVariantResolver;

    protected function setUp(): void
    {
        $this->component = new WishlistComponent(
            self::createMock(ProductRepositoryInterface::class),
            $this->productVariantRepository = self::createMock(ProductVariantRepositoryInterface::class),
            $this->entityManager = self::createMock(EntityManagerInterface::class),
            $this->requestStack = new RequestStack(),
            $this->productVariantResolver = self::createMock(ProductVariantResolverInterface::class),
            $this->wishlistProvider = self::createMock(WishlistProviderInterface::class),
            self::createMock(WishlistTokenProviderInterface::class),
            $this->wishlistProductRepository = self::createMock(RepositoryInterface::class),
            $this->wishlistProductFactory = self::createMock(FactoryInterface::class),
        );
    }

    public function test_add_to_wishlist_adds_product(): void
    {
        $variant = $this->mockVariant(10);
        $wishlist = $this->mockWishlist(1);
        $wishlistProduct = self::createMock(WishlistProduct::class);

        $this->wishlistProvider->method('provide')->willReturn($wishlist);
        $this->wishlistProductRepository->method('findBy')->willReturn([]);
        $this->wishlistProductFactory->method('createNew')->willReturn($wishlistProduct);

        $wishlistProduct->expects($this->once())->method('setProductVariant')->with($variant);
        $wishlist->expects($this->once())->method('addWishlistProduct')->with($wishlistProduct);
        $this->entityManager->expects($this->once())->method('persist')->with($wishlistProduct);
        $this->entityManager->expects($this->once())->method('flush');

        $this->mockFlashBag('Product has been added to wishlist');

        $this->component->variant = $variant;
        $this->component->addToWishlist();
    }

    public function test_add_to_wishlist_when_product_already_exists_on_wishlist(): void
    {
        $variant = $this->mockVariant(10);
        $wishlist = $this->mockWishlist(1);

        $this->wishlistProvider->expects($this->once())->method('provide')->willReturn($wishlist);
        $this->wishlistProductRepository->expects($this->once())->method('findBy')->willReturn([self::createMock(WishlistProduct::class)]);

        $this->component->variant = $variant;
        $this->component->addToWishlist();
    }

    public function test_update_product_variant_updates_variant_correctly(): void
    {
        $currentVariant = $this->mockVariant(1, true);
        $newVariant = $this->mockVariant(2, true);

        $this->productVariantRepository->method('find')->with(2)->willReturn($newVariant);
        $this->component->variant = $currentVariant;

        $this->component->updateProductVariant(2);

        $this->assertSame($newVariant, $this->component->variant);
    }

    public function test_update_product_variant_with_variant_null(): void
    {
        $this->component->updateProductVariant(null);
        $this->assertNull($this->component->variant);
    }

    public function test_update_product_variant_does_not_update_with_same_variant(): void
    {
        $variant = $this->mockVariant(1, true);

        $this->productVariantRepository->method('find')->with(1)->willReturn($variant);
        $this->component->variant = $variant;

        $this->component->updateProductVariant(1);

        $this->assertSame($variant, $this->component->variant);
    }

    public function test_update_product_variant_resets_variant_on_invalid_data(): void
    {
        $currentVariant = $this->mockVariant(1, true);
        $invalidVariant = $this->mockVariant(2, false);

        $this->productVariantRepository->method('find')->with(2)->willReturn($invalidVariant);
        $this->component->variant = $currentVariant;

        $this->component->updateProductVariant(2);

        $this->assertNull($this->component->variant);
    }

    public function test_post_mount_sets_variant_correctly(): void
    {
        $product = self::createMock(ProductInterface::class);
        $resolvedVariant = self::createMock(ProductVariantInterface::class);

        $this->component->product = $product;
        $this->productVariantResolver->method('getVariant')->willReturn($resolvedVariant);

        $this->component->postMount();

        $this->assertSame($resolvedVariant, $this->component->variant);
    }

    public function test_post_mount_handles_null_variant(): void
    {
        $product = self::createMock(ProductInterface::class);

        $this->component->product = $product;
        $this->productVariantResolver->method('getVariant')->willReturn(null);

        $this->component->postMount();

        $this->assertNull($this->component->variant);
    }

    private function mockVariant(int $id, bool $enabled = true): ProductVariantInterface
    {
        $variant = self::createMock(ProductVariantInterface::class);
        $variant->method('getId')->willReturn($id);
        $variant->method('isEnabled')->willReturn($enabled);

        return $variant;
    }

    private function mockWishlist(int $id): WishlistInterface
    {
        $wishlist = self::createMock(WishlistInterface::class);
        $wishlist->method('getId')->willReturn($id);

        return $wishlist;
    }

    private function mockFlashBag(string $message): void
    {
        $flashBag = self::createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())->method('add')->with('success', $message);

        $session = self::createMock(FlashBagAwareSessionInterface::class);
        $session->method('getFlashBag')->willReturn($flashBag);

        $request = new Request();
        $request->setSession($session);
        $this->requestStack->push($request);
    }
}
