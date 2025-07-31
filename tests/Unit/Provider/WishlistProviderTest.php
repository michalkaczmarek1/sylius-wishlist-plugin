<?php

declare(strict_types=1);

namespace Tests\SyliusAcademy\WishlistPlugin\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistInterface;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistProduct;
use SyliusAcademy\WishlistPlugin\Provider\WishlistProvider;
use SyliusAcademy\WishlistPlugin\Provider\WishlistTokenProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class WishlistProviderTest extends TestCase
{
    private TokenStorageInterface $tokenStorage;

    private RepositoryInterface $wishlistRepository;

    private FactoryInterface $wishlistFactory;

    private WishlistTokenProviderInterface $wishlistTokenProvider;

    protected function setUp(): void
    {
        $this->tokenStorage = self::createMock(TokenStorageInterface::class);
        $this->wishlistRepository = self::createMock(RepositoryInterface::class);
        $this->wishlistFactory = self::createMock(FactoryInterface::class);
        $this->wishlistTokenProvider = self::createMock(WishlistTokenProviderInterface::class);
    }

    public function test_creates_new_wishlist_without_user_and_existing_wishlist(): void
    {
        $token = 'token123';

        $this->tokenStorage->method('getToken')->willReturn(null);
        $this->wishlistTokenProvider->method('provide')->willReturn($token);
        $this->wishlistRepository->method('findOneBy')->willReturn(null);

        $wishlist = self::createMock(WishlistInterface::class);
        $wishlist->expects($this->once())->method('setWishlistToken')->with($token);

        $this->wishlistFactory->method('createNew')->willReturn($wishlist);

        $provider = new WishlistProvider(
            $this->tokenStorage,
            $this->wishlistRepository,
            $this->wishlistFactory,
            $this->wishlistTokenProvider,
        );

        $result = $provider->provide();
        $this->assertSame($wishlist, $result);
    }

    public function test_creates_new_wishlist_without_existing_wishlist_but_with_user(): void
    {
        $token = 'token123';

        $this->wishlistTokenProvider->method('provide')->willReturn($token);
        $this->wishlistRepository->method('findOneBy')->willReturn(null);

        $user = self::createMock(ShopUserInterface::class);
        $token = self::createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $wishlist = self::createMock(WishlistInterface::class);

        $this->wishlistFactory->method('createNew')->willReturn($wishlist);

        $provider = new WishlistProvider(
            $this->tokenStorage,
            $this->wishlistRepository,
            $this->wishlistFactory,
            $this->wishlistTokenProvider,
        );

        $result = $provider->provide();
        $this->assertSame($wishlist, $result);
    }

    public function test_returns_wishlist_found_by_token_and_customer(): void
    {
        $tokenValue = 'abc123';
        $user = self::createMock(ShopUserInterface::class);

        $token = self::createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);
        $this->wishlistTokenProvider->method('provide')->willReturn($tokenValue);

        $wishlist = self::createMock(WishlistInterface::class);

        $this->wishlistRepository->method('findOneBy')->willReturnCallback(
            fn ($criteria) => ($criteria['wishlistToken'] ?? null) && ($criteria['customer'] ?? null) ? $wishlist : null,
        );

        $provider = new WishlistProvider(
            $this->tokenStorage,
            $this->wishlistRepository,
            $this->wishlistFactory,
            $this->wishlistTokenProvider,
        );

        $this->wishlistFactory->expects($this->never())->method('createNew');

        $result = $provider->provide();
        $this->assertSame($wishlist, $result);
    }

    public function test_returns_wishlist_found_by_customer_only(): void
    {
        $tokenValue = 'abc123';
        $user = self::createMock(ShopUserInterface::class);

        $token = self::createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);
        $this->wishlistTokenProvider->method('provide')->willReturn($tokenValue);

        $this->wishlistRepository->method('findOneBy')->willReturnCallback(
            function (array $criteria) use ($user) {
                if (isset($criteria['customer']) && !isset($criteria['wishlistToken'])) {
                    return self::createMock(WishlistInterface::class);
                }

                return null;
            },
        );

        $provider = new WishlistProvider(
            $this->tokenStorage,
            $this->wishlistRepository,
            $this->wishlistFactory,
            $this->wishlistTokenProvider,
        );

        $this->wishlistFactory->expects($this->never())->method('createNew');

        $result = $provider->provide();
        $this->assertInstanceOf(WishlistInterface::class, $result);
    }

    public function test_merges_products_when_both_token_and_customer_wishlists_exist(): void
    {
        $tokenValue = 'abc123';
        $user = self::createMock(ShopUserInterface::class);

        $token = self::createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);
        $this->wishlistTokenProvider->method('provide')->willReturn($tokenValue);

        $wishlistFromToken = self::createMock(WishlistInterface::class);
        $wishlistFromCustomer = self::createMock(WishlistInterface::class);
        $wishlistProduct = self::createMock(WishlistProduct::class);

        $wishlistFromToken->method('getWishlistProducts')->willReturn([$wishlistProduct]);
        $wishlistFromCustomer->expects($this->once())->method('addWishlistProduct')->with($wishlistProduct);

        $callCount = 0;

        $this->wishlistRepository->method('findOneBy')->willReturnCallback(function ($criteria) use (
            $wishlistFromToken,
            $wishlistFromCustomer,
            $user,
            &$callCount
        ) {
            if (isset($criteria['wishlistToken'], $criteria['customer'])) {
                ++$callCount;

                return $wishlistFromCustomer;
            }
            if (isset($criteria['wishlistToken']) && !isset($criteria['customer'])) {
                ++$callCount;

                return $wishlistFromToken;
            }

            return null;
        });

        $provider = new WishlistProvider(
            $this->tokenStorage,
            $this->wishlistRepository,
            $this->wishlistFactory,
            $this->wishlistTokenProvider,
        );

        $result = $provider->provide();
        $this->assertSame($wishlistFromCustomer, $result);
    }
}
