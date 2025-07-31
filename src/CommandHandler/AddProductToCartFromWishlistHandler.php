<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\CommandHandler;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Sylius\Resource\Factory\FactoryInterface;
use SyliusAcademy\WishlistPlugin\Command\AddProductToCartFromWishlist;
use SyliusAcademy\WishlistPlugin\Helper\FlashHelperInterface;

final readonly class AddProductToCartFromWishlistHandler
{
    public function __construct(
        private FactoryInterface $orderItemFactory,
        private OrderModifierInterface $orderModifier,
        private OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        private EntityManagerInterface $entityManager,
        private AvailabilityCheckerInterface $availabilityChecker,
        private FlashHelperInterface $flashHelper,
    ) {
    }

    public function __invoke(AddProductToCartFromWishlist $addProductToCartFromWishlist): void
    {
        $quantity = $addProductToCartFromWishlist->getQuantity();
        $productVariant = $addProductToCartFromWishlist->getProductVariant();
        if (!$this->availabilityChecker->isStockSufficient(
            $productVariant,
            $quantity,
        )) {
            $this->flashHelper->createMessage(
                'error',
                'Product has not been added to cart, qty %d is not enough',
                $quantity,
            );

            return;
        }

        $orderItem = $this->orderItemFactory->createNew();
        if (!$orderItem instanceof OrderItemInterface) {
            $this->flashHelper->createMessage(
                'error',
                'Order item does not exist',
                $quantity,
            );

            return;
        }
        $orderItem->setVariant($productVariant);
        $this->orderItemQuantityModifier->modify($orderItem, $quantity);

        $cart = $addProductToCartFromWishlist->getCart();
        $this->orderModifier->addToOrder($cart, $orderItem);

        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        $this->flashHelper->createMessage(
            'success',
            'Product has been added to cart, qty %d',
            $quantity,
        );
    }
}
