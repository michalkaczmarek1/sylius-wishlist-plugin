<?php

declare(strict_types=1);

namespace Sylius\Tests\Api\Twig\Component\Twig\Component;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistInterface;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistProductInterface;
use SyliusAcademy\WishlistPlugin\Provider\WishlistProviderInterface;
use SyliusAcademy\WishlistPlugin\Twig\Component\WishlistComponent;

final class WishlistComponentTest extends TestCase
{
    private ProductRepositoryInterface $productRepository;

    private ProductVariantRepositoryInterface $productVariantRepository;

    private EntityManagerInterface $entityManager;

    private ProductVariantResolverInterface $productVariantResolver;

    private WishlistProviderInterface $wishlistProvider;

    private RepositoryInterface $wishlistProductRepository;

    private FactoryInterface $wishlistProductFactory;

    private LoggerInterface $logger;

    private WishlistComponent $wishlistComponent;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->productVariantRepository = $this->createMock(ProductVariantRepositoryInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->productVariantResolver = $this->createMock(ProductVariantResolverInterface::class);
        $this->wishlistProvider = $this->createMock(WishlistProviderInterface::class);
        $this->wishlistProductRepository = $this->createMock(RepositoryInterface::class);
        $this->wishlistProductFactory = $this->createMock(FactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->wishlistComponent = new WishlistComponent(
            $this->productRepository,
            $this->productVariantRepository,
            $this->entityManager,
            $this->productVariantResolver,
            $this->wishlistProvider,
            $this->wishlistProductRepository,
            $this->wishlistProductFactory,
            $this->logger,
        );
    }

    public function test_add_to_wishlist_sets_is_in_wishlist_true_when_product_added_successfully(): void
    {
        $wishlist = $this->createMock(WishlistInterface::class);
        $wishlistProduct = $this->createMock(WishlistProductInterface::class);
        $variant = $this->createMock(ProductVariantInterface::class);

        $variantId = 123;
        $wishlistId = 456;

        $this->wishlistComponent->variant = $variant;

        $variant->method('getId')->willReturn($variantId);
        $this->wishlistProvider->method('provide')->willReturn($wishlist);
        $wishlist->method('getId')->willReturn($wishlistId);

        $this->wishlistProductRepository
            ->method('findBy')
            ->with(['productVariant' => $variantId, 'wishlist' => $wishlistId])
            ->willReturn([]);

        $this->wishlistProductFactory->method('createNew')->willReturn($wishlistProduct);
        $wishlist->expects($this->once())->method('addWishlistProduct')->with($wishlistProduct);
        $this->entityManager->expects($this->once())->method('persist')->with($wishlistProduct);

        $this->wishlistComponent->addToWishlist();

        $this->assertFalse($this->wishlistComponent->isInWishlist);
    }

    public function test_add_to_wishlist_logs_error_on_exception(): void
    {
        $this->wishlistProvider->method('provide')->willThrowException(new Exception('An error occurred'));
        $this->logger->expects($this->once())->method('error')->with('An error occurred');
        $this->wishlistComponent->addToWishlist();
    }

    public function test_add_to_wishlist_does_nothing_when_product_already_in_wishlist(): void
    {
        $wishlist = $this->createMock(WishlistInterface::class);
        $variant = $this->createMock(ProductVariantInterface::class);

        $variantId = 123;
        $wishlistId = 456;

        $this->wishlistComponent->variant = $variant;

        $variant->method('getId')->willReturn($variantId);
        $this->wishlistProvider->method('provide')->willReturn($wishlist);
        $wishlist->method('getId')->willReturn($wishlistId);

        $this->wishlistProductRepository
            ->method('findBy')
            ->with(['productVariant' => $variantId, 'wishlist' => $wishlistId])
            ->willReturn([true]);

        $this->wishlistComponent->addToWishlist();

        $this->assertTrue($this->wishlistComponent->isInWishlist);
    }

    public function test_post_mount_sets_variant_when_product_is_not_null(): void
    {
        $product = $this->createMock(ProductInterface::class);
        $variant = $this->createMock(ProductVariantInterface::class);

        $this->wishlistComponent->product = $product;

        $this->productVariantResolver->method('getVariant')->with($product)->willReturn($variant);

        $this->wishlistComponent->postMount();

        $this->assertSame($variant, $this->wishlistComponent->variant);
    }

    public function test_post_mount_sets_is_in_wishlist_to_true_when_product_in_wishlist(): void
    {
        $wishlist = $this->createMock(WishlistInterface::class);
        $variant = $this->createMock(ProductVariantInterface::class);

        $variantId = 123;
        $wishlistId = 456;

        $this->wishlistComponent->variant = $variant;

        $variant->method('getId')->willReturn($variantId);
        $this->wishlistProvider->method('provide')->willReturn($wishlist);
        $wishlist->method('getId')->willReturn($wishlistId);

        $this->wishlistProductRepository
            ->method('findBy')
            ->with(['productVariant' => $variantId, 'wishlist' => $wishlistId])
            ->willReturn([true]);

        $this->wishlistComponent->postMount();

        $this->assertTrue($this->wishlistComponent->isInWishlist);
    }

    public function test_post_mount_sets_is_in_wishlist_to_false_when_product_not_in_wishlist(): void
    {
        $wishlist = $this->createMock(WishlistInterface::class);
        $variant = $this->createMock(ProductVariantInterface::class);

        $variantId = 123;
        $wishlistId = 456;

        $this->wishlistComponent->variant = $variant;

        $variant->method('getId')->willReturn($variantId);
        $this->wishlistProvider->method('provide')->willReturn($wishlist);
        $wishlist->method('getId')->willReturn($wishlistId);

        $this->wishlistProductRepository
            ->method('findBy')
            ->with(['productVariant' => $variantId, 'wishlist' => $wishlistId])
            ->willReturn([]);

        $this->wishlistComponent->postMount();

        $this->assertFalse($this->wishlistComponent->isInWishlist);
    }

    public function test_post_mount_logs_error_on_exception(): void
    {
        $this->wishlistProvider->method('provide')->willThrowException(new Exception('An error occurred'));
        $this->logger->expects($this->once())->method('error')->with('An error occurred');
        $this->wishlistComponent->postMount();
    }

    public function test_update_product_variant_updates_variant_when_valid_id_provided(): void
    {
        $variantId = 123;
        $newVariant = $this->createMock(ProductVariantInterface::class);

        $this->productVariantRepository->method('find')->with($variantId)->willReturn($newVariant);
        $newVariant->method('isEnabled')->willReturn(true);

        $this->wishlistComponent->updateProductVariant($variantId);

        $this->assertSame($newVariant, $this->wishlistComponent->variant);
    }

    public function test_update_product_variant_sets_variant_to_null_when_variant_is_disabled(): void
    {
        $variantId = 123;
        $newVariant = $this->createMock(ProductVariantInterface::class);

        $this->productVariantRepository->method('find')->with($variantId)->willReturn($newVariant);
        $newVariant->method('isEnabled')->willReturn(false);

        $this->wishlistComponent->updateProductVariant($variantId);

        $this->assertNull($this->wishlistComponent->variant);
    }

    public function test_update_product_variant_does_nothing_when_variant_id_is_null(): void
    {
        $this->wishlistComponent->variant = $this->createMock(ProductVariantInterface::class);

        $this->wishlistComponent->updateProductVariant(null);

        $this->assertNotNull($this->wishlistComponent->variant);
    }

    public function test_update_product_variant_does_nothing_when_provided_variant_is_same_as_current(): void
    {
        $variantId = 123;
        $currentVariant = $this->createMock(ProductVariantInterface::class);

        $this->wishlistComponent->variant = $currentVariant;

        $this->productVariantRepository->method('find')->with($variantId)->willReturn($currentVariant);

        $this->wishlistComponent->updateProductVariant($variantId);

        $this->assertSame($currentVariant, $this->wishlistComponent->variant);
    }
}
