<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Controller\Action;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use SyliusAcademy\WishlistPlugin\Command\AddProductToCartFromWishlist;
use SyliusAcademy\WishlistPlugin\Helper\FlashHelperInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AddProductToCartFromWishlistAction
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private CartContextInterface $cartContext,
        private UrlGeneratorInterface $urlGenerator,
        private FlashHelperInterface $flashHelper,
        private RepositoryInterface $productVariantRepository,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $productVariantId = (int) $request->get('product_variant_id');
        $url = $this->urlGenerator->generate('sylius_shop_cart_summary');
        if ($productVariantId <= 0) {
            $this->flashHelper->createMessage(
                'error',
                'Product variant given %d is not exist',
                $productVariantId,
            );

            return new RedirectResponse($url);
        }

        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->productVariantRepository->find($productVariantId);
        if (!$productVariant instanceof ProductVariantInterface) {
            $this->flashHelper->createMessage(
                'error',
                'Product variant does not exist.',
            );

            return new RedirectResponse($url);
        }

        $quantity = (int) $request->get('quantity');
        if ($quantity <= 0) {
            $this->flashHelper->createMessage(
                'error',
                'Quantity given %d is wrong',
                $quantity,
            );

            return new RedirectResponse($url);
        }

        $this->messageBus->dispatch(new AddProductToCartFromWishlist(
            $this->cartContext->getCart(),
            $productVariant,
            $quantity,
        ));

        return new RedirectResponse($url);
    }
}
