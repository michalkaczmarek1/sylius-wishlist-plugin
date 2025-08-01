<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Provider;

use Exception;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class WishlistProvider implements WishlistProviderInterface
{
    /**
     * @param FactoryInterface<WishlistInterface> $wishlistFactory
     * @param RepositoryInterface<WishlistInterface> $wishlistRepository
     */
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RepositoryInterface $wishlistRepository,
        private FactoryInterface $wishlistFactory,
        private WishlistTokenProviderInterface $wishlistTokenProvider,
    ) {
    }

    /**
     * @throws Exception
     */
    public function provide(): WishlistInterface
    {
        $customer = $this->getCurrentUser();
        $token = $this->wishlistTokenProvider->provide();
        /** @var WishlistInterface $wishlistFromToken */
        $wishlistFromToken = $this->wishlistRepository->findOneBy([
            'wishlistToken' => $token,
            'customer' => null,
        ]);
        /** @var WishlistInterface $wishlist */
        $wishlist = null;
        if ($customer instanceof ShopUserInterface) {
            $wishlist = $this->getWishlist($token, $customer, $wishlistFromToken);
        }

        if (null === $wishlist) {
            $wishlist = $wishlistFromToken;
        }

        if (!$wishlist instanceof WishlistInterface) {
            /** @var WishlistInterface $wishlist */
            $wishlist = $this->wishlistFactory->createNew();
            $wishlist->setWishlistToken($token);
            if ($customer instanceof ShopUserInterface) {
                $wishlist->setCustomer($customer);
            }
        }

        return $wishlist;
    }

    private function getCurrentUser(): ?ShopUserInterface
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return null;
        }

        $user = $token->getUser();

        return $user instanceof ShopUserInterface ? $user : null;
    }

    private function getWishlist(
        string $token,
        ShopUserInterface $customer,
        ?WishlistInterface $wishlistFromToken,
    ): ?WishlistInterface {
        /** @var WishlistInterface|null $wishlist */
        $wishlist = $this->wishlistRepository->findOneBy([
            'wishlistToken' => $token,
            'customer' => $customer,
        ]);

        if (null === $wishlist) {
            $wishlist = $this->wishlistRepository->findOneBy([
                'customer' => $customer,
            ]);
        }

        if (null !== $wishlist && null === $wishlistFromToken) {
            return $wishlist;
        }

        if (null === $wishlist) {
            return $wishlistFromToken;
        }

        $wishlistProductsFromToken = $wishlistFromToken?->getWishlistProducts();
        if ($wishlistProductsFromToken !== null) {
            foreach ($wishlistProductsFromToken as $wishlistProduct) {
                $wishlist->addWishlistProduct($wishlistProduct);
            }
        }

        return $wishlist;
    }
}
