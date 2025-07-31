<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Provider;

use SyliusAcademy\WishlistPlugin\Provider\Token\WishlistToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class WishlistTokenProvider implements WishlistTokenProviderInterface
{
    public const COOKIE_KEY = 'wishlist_token';

    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function provide(): string
    {
        /** @var ?Request $mainRequest */
        $mainRequest = $this->requestStack->getMainRequest();
        if (null === $mainRequest) {
            return (string) new WishlistToken();
        }

        $wishlistCookieToken = $mainRequest->cookies->get(self::COOKIE_KEY);

        if (null !== $wishlistCookieToken) {
            return (string) $wishlistCookieToken;
        }

        $wishlistCookieToken = $mainRequest->attributes->get(self::COOKIE_KEY);
        if (null !== $wishlistCookieToken) {
            return (string) $wishlistCookieToken;
        }

        return (string) new WishlistToken();
    }
}
