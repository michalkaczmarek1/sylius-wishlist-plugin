<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Twig\Component;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Sylius\Bundle\ShopBundle\Twig\Component\Product\AddToCartFormComponent;
use Sylius\Bundle\ShopBundle\Twig\Component\Product\Trait\ProductLivePropTrait;
use Sylius\Bundle\ShopBundle\Twig\Component\Product\Trait\ProductVariantLivePropTrait;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use SyliusAcademy\WishlistPlugin\Provider\WishlistProviderInterface;
use SyliusAcademy\WishlistPlugin\Provider\WishlistTokenProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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

    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected ProductVariantRepositoryInterface $productVariantRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly ProductVariantResolverInterface $productVariantResolver,
        private readonly WishlistProviderInterface $wishlistProvider,
        private readonly WishlistTokenProviderInterface $wishlistTokenProvider,
        private readonly RepositoryInterface $wishlistProductRepository,
        private readonly FactoryInterface $wishlistProductFactory,
    ) {
        $this->initializeProduct($productRepository);
        $this->initializeProductVariant($productVariantRepository);
    }

    #[PostMount]
    public function postMount(): void
    {
        /** @var ProductVariantInterface|null $variant * */
        $variant = $this->productVariantResolver->getVariant($this->product);

        $this->variant = $variant;
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

        $this->variant = $changedVariant->isEnabled() ? $changedVariant : null;
    }

    /**
     * @throws Exception
     */
    #[LiveAction]
    public function addToWishlist(): void
    {
        $wishlist = $this->wishlistProvider->provide();
        if ($this->wishlistProductRepository->findBy([
            'productVariant' => $this->variant->getId(),
            'wishlist' => $wishlist->getId(),
        ])) {
            return;
        }

        $wishlistProduct = $this->wishlistProductFactory->createNew();
        $wishlistProduct->setProductVariant($this->variant);
        $wishlist->addWishlistProduct($wishlistProduct);
        $this->entityManager->persist($wishlistProduct);
        $this->entityManager->flush();

        $this->requestStack->getSession()->getFlashBag()->add('success', 'Product has been added to wishlist');
    }
}
