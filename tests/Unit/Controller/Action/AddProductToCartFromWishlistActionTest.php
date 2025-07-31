<?php

declare(strict_types=1);

namespace Tests\SyliusAcademy\WishlistPlugin\Unit\Controller\Action;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use SyliusAcademy\WishlistPlugin\Command\AddProductToCartFromWishlist;
use SyliusAcademy\WishlistPlugin\Controller\Action\AddProductToCartFromWishlistAction;
use SyliusAcademy\WishlistPlugin\Helper\FlashHelperInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AddProductToCartFromWishlistActionTest extends TestCase
{
    private MessageBusInterface $messageBus;

    private CartContextInterface $cartContext;

    private UrlGeneratorInterface $urlGenerator;

    private FlashHelperInterface $flashHelper;

    private RepositoryInterface $productVariantRepository;

    private string $redirectUrl = '/cart-summary';

    protected function setUp(): void
    {
        $this->messageBus = self::createMock(MessageBusInterface::class);
        $this->cartContext = self::createMock(CartContextInterface::class);
        $this->urlGenerator = self::createMock(UrlGeneratorInterface::class);
        $this->flashHelper = self::createMock(FlashHelperInterface::class);
        $this->productVariantRepository = self::createMock(RepositoryInterface::class);

        $this->urlGenerator->method('generate')->with('sylius_shop_cart_summary')->willReturn($this->redirectUrl);
    }

    /**
     * @dataProvider provideInvalidRequests
     */
    public function test_redirects_with_error_on_invalid_input(array $requestData, string $expectedMessage, array $expectedArgs, bool $useProductVariant): void
    {
        $request = new Request($requestData);
        if ($useProductVariant) {
            $variant = self::createMock(ProductVariantInterface::class);
            $this->productVariantRepository
                ->method('find')
                ->with($requestData['product_variant_id'])
                ->willReturn($variant);
        }

        $this->flashHelper->expects($this->once())
            ->method('createMessage')
            ->with('error', $expectedMessage, ...$expectedArgs);

        $action = $this->createAction();

        $response = $action($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($this->redirectUrl, $response->getTargetUrl());
    }

    public function test_dispatches_command_when_input_is_valid(): void
    {
        $cart = self::createMock(OrderInterface::class);
        $variant = self::createMock(ProductVariantInterface::class);

        $request = new Request([
            'product_variant_id' => 1,
            'quantity' => 2,
        ]);

        $this->productVariantRepository
            ->method('find')
            ->with(1)
            ->willReturn($variant);

        $this->cartContext
            ->method('getCart')
            ->willReturn($cart);

        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (AddProductToCartFromWishlist $message) use ($cart, $variant) {
                return $message->getCart() === $cart &&
                    $message->getProductVariant() === $variant &&
                    $message->getQuantity() === 2;
            }))
            ->willReturn(new Envelope(new AddProductToCartFromWishlist($cart, $variant, 2)))
        ;

        $this->flashHelper->expects($this->never())->method('createMessage');

        $action = $this->createAction();

        $response = $action($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($this->redirectUrl, $response->getTargetUrl());
    }

    public static function provideInvalidRequests(): array
    {
        return [
            'invalid product_variant_id (0)' => [
                ['product_variant_id' => 0],
                'Product variant given %d is not exist',
                [0],
                false,
            ],
            'non-existent product_variant' => [
                ['product_variant_id' => 99],
                'Product variant does not exist.',
                [],
                false,
            ],
            'invalid quantity (0)' => [
                ['product_variant_id' => 1, 'quantity' => 0],
                'Quantity given %d is wrong',
                [0],
                true,
            ],
        ];
    }

    private function createAction(): AddProductToCartFromWishlistAction
    {
        return new AddProductToCartFromWishlistAction(
            $this->messageBus,
            $this->cartContext,
            $this->urlGenerator,
            $this->flashHelper,
            $this->productVariantRepository,
        );
    }
}
