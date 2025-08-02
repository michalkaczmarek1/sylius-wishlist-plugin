<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Twig\Component;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ShopBundle\Twig\Component\Product\AddToCartFormComponent;
use Sylius\Bundle\ShopBundle\Twig\Component\Product\Trait\ProductLivePropTrait;
use Sylius\Bundle\ShopBundle\Twig\Component\Product\Trait\ProductVariantLivePropTrait;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistProductInterface;
use SyliusAcademy\WishlistPlugin\Provider\WishlistProviderInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent]
final class WishlistComponent
{
    use DefaultActionTrait;
    use HookableLiveComponentTrait;
    use TemplatePropTrait;
    use ComponentToolsTrait;
    use ProductLivePropTrait;
    use ProductVariantLivePropTrait;

    public bool $isInWishlist = false;

    /**
     * @param ProductRepositoryInterface<ProductInterface> $productRepository
     * @param ProductVariantRepositoryInterface<ProductVariantInterface> $productVariantRepository
     * @param FactoryInterface<WishlistProductInterface> $wishlistProductFactory
     * @param RepositoryInterface<WishlistProductInterface> $wishlistProductRepository
     */
    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected ProductVariantRepositoryInterface $productVariantRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductVariantResolverInterface $productVariantResolver,
        private readonly WishlistProviderInterface $wishlistProvider,
        private readonly RepositoryInterface $wishlistProductRepository,
        private readonly FactoryInterface $wishlistProductFactory,
        private readonly LoggerInterface $logger,
    ) {
        $this->initializeProduct($productRepository);
        $this->initializeProductVariant($productVariantRepository);
    }

    #[PostMount]
    public function postMount(): void
    {
        if (null !== $this->product) {
            /** @var ProductVariantInterface|null $variant * */
            $variant = $this->productVariantResolver->getVariant($this->product);
            $this->variant = $variant;
        }

        try {
            $wishlist = $this->wishlistProvider->provide();
            $this->isInWishlist = $this->wishlistProductRepository->findBy([
                    'productVariant' => $this->variant?->getId(),
                    'wishlist' => $wishlist->getId(),
                ]) !== [];
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    #[LiveListener(AddToCartFormComponent::SYLIUS_SHOP_VARIANT_CHANGED)]
    public function updateProductVariant(#[LiveArg] mixed $variantId): void
    {
        if (null === $variantId) {
            return;
        }

        $changedVariant = $this->productVariantRepository->find($variantId);

        if ($changedVariant === $this->variant) {
            return;
        }

        if (null !== $changedVariant) {
            $this->variant = $changedVariant->isEnabled() ? $changedVariant : null;
        }
    }

    #[LiveAction]
    public function addToWishlist(): void
    {
        try {
            $wishlist = $this->wishlistProvider->provide();
            if ($this->wishlistProductRepository->findBy([
                'productVariant' => $this->variant?->getId(),
                'wishlist' => $wishlist->getId(),
            ])) {
                $this->isInWishlist = true;

                return;
            }

            $wishlistProduct = $this->wishlistProductFactory->createNew();
            $wishlistProduct->setProductVariant($this->variant);
            $wishlist->addWishlistProduct($wishlistProduct);
            $this->entityManager->persist($wishlistProduct);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
