<?php

declare(strict_types=1);

namespace Tests\SyliusAcademy\WishlistPlugin\Unit\CommandHandler;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Sylius\Resource\Factory\FactoryInterface;
use SyliusAcademy\WishlistPlugin\Command\AddProductToCartFromWishlist;
use SyliusAcademy\WishlistPlugin\CommandHandler\AddProductToCartFromWishlistHandler;
use SyliusAcademy\WishlistPlugin\Helper\FlashHelperInterface;

class AddProductToCartFromWishlistHandlerTest extends TestCase
{
    private FactoryInterface $orderItemFactory;

    private OrderModifierInterface $orderModifier;

    private OrderItemQuantityModifierInterface $orderItemQuantityModifier;

    private EntityManagerInterface $entityManager;

    private AvailabilityCheckerInterface $availabilityChecker;

    private FlashHelperInterface $flashHelper;

    protected function setUp(): void
    {
        $this->orderItemFactory = self::createMock(FactoryInterface::class);
        $this->orderModifier = self::createMock(OrderModifierInterface::class);
        $this->orderItemQuantityModifier = self::createMock(OrderItemQuantityModifierInterface::class);
        $this->entityManager = self::createMock(EntityManagerInterface::class);
        $this->availabilityChecker = self::createMock(AvailabilityCheckerInterface::class);
        $this->flashHelper = self::createMock(FlashHelperInterface::class);
    }

    public function test_adds_product_to_cart_when_stock_is_sufficient(): void
    {
        $cart = self::createMock(OrderInterface::class);
        $productVariant = self::createMock(ProductVariantInterface::class);
        $quantity = 3;

        $command = new AddProductToCartFromWishlist($cart, $productVariant, $quantity);

        $orderItem = self::createMock(OrderItemInterface::class);
        $this->availabilityChecker->method('isStockSufficient')->with($productVariant, $quantity)->willReturn(true);
        $this->orderItemFactory->method('createNew')->willReturn($orderItem);

        $orderItem->expects($this->once())->method('setVariant')->with($productVariant);
        $this->orderItemQuantityModifier->expects($this->once())->method('modify')->with($orderItem, $quantity);
        $this->orderModifier->expects($this->once())->method('addToOrder')->with($cart, $orderItem);
        $this->entityManager->expects($this->once())->method('persist')->with($cart);
        $this->entityManager->expects($this->once())->method('flush');
        $this->flashHelper->expects($this->once())->method('createMessage')->with('success', 'Product has been added to cart, qty %d', $quantity);

        $handler = $this->createHandler();
        $handler($command);
    }

    public function test_does_not_add_product_if_stock_is_insufficient(): void
    {
        $cart = self::createMock(OrderInterface::class);
        $productVariant = self::createMock(ProductVariantInterface::class);
        $quantity = 10;

        $command = new AddProductToCartFromWishlist($cart, $productVariant, $quantity);

        $this->availabilityChecker->method('isStockSufficient')->willReturn(false);
        $this->flashHelper->expects($this->once())->method('createMessage')
            ->with('error', 'Product has not been added to cart, qty %d is not enough', $quantity);

        $handler = $this->createHandler();
        $handler($command);
    }

    public function test_does_not_add_product_if_order_item_is_invalid(): void
    {
        $cart = self::createMock(OrderInterface::class);
        $productVariant = self::createMock(ProductVariantInterface::class);
        $quantity = 1;

        $command = new AddProductToCartFromWishlist($cart, $productVariant, $quantity);

        $this->availabilityChecker->method('isStockSufficient')->willReturn(true);
        $this->orderItemFactory->method('createNew')->willReturn(new \stdClass());
        $this->flashHelper->expects($this->once())->method('createMessage')
            ->with('error', 'Order item does not exist', $quantity);

        $handler = $this->createHandler();
        $handler($command);
    }

    private function createHandler(): AddProductToCartFromWishlistHandler
    {
        return new AddProductToCartFromWishlistHandler(
            $this->orderItemFactory,
            $this->orderModifier,
            $this->orderItemQuantityModifier,
            $this->entityManager,
            $this->availabilityChecker,
            $this->flashHelper,
        );
    }
}
