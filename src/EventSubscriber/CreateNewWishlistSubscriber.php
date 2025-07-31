<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\EventSubscriber;

use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistInterface;
use SyliusAcademy\WishlistPlugin\Provider\WishlistProviderInterface;
use SyliusAcademy\WishlistPlugin\Provider\WishlistTokenProvider;
use SyliusAcademy\WishlistPlugin\Provider\WishlistTokenProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\Assert\Assert;

final readonly class CreateNewWishlistSubscriber implements EventSubscriberInterface
{
    private const ALLOWED_ENDPOINTS_PREFIX = '/wishlist';

    public function __construct(
        private WishlistProviderInterface $wishlistProvider,
        private WishlistTokenProviderInterface $wishlistTokenProvider,
        private RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 1]],
            KernelEvents::RESPONSE => [['onKernelResponse', 0]],
        ];
    }

    /**
     * @throws \Exception
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $mainRequest = $this->getMainRequest();

        $currentPath = $mainRequest->getPathInfo();
        $isWishlistUrl = str_contains($currentPath, self::ALLOWED_ENDPOINTS_PREFIX);
        if (!$isWishlistUrl) {
            return;
        }

        /** @var WishlistInterface $wishlist */
        $wishlist = $this->wishlistProvider->provide();

        $wishlistCookieToken = $mainRequest->cookies->get(WishlistTokenProvider::COOKIE_KEY);

        if ($wishlist->getId()) {
            if (null === $wishlistCookieToken) {
                $mainRequest->attributes->set(WishlistTokenProvider::COOKIE_KEY, $wishlist->getWishlistToken());
            }

            return;
        }

        $tokenFromWishlist = $wishlist->getWishlistToken();
        if (null === $wishlistCookieToken && null === $tokenFromWishlist) {
            $wishlistCookieToken = $this->wishlistTokenProvider->provide();
        }

        if (!empty($tokenFromWishlist)) {
            $mainRequest->attributes->set(WishlistTokenProvider::COOKIE_KEY, $tokenFromWishlist);

            return;
        }

        $mainRequest->attributes->set(WishlistTokenProvider::COOKIE_KEY, $wishlistCookieToken);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $mainRequest = $this->getMainRequest();

        $tokenWasGenerated = $mainRequest->attributes->has(WishlistTokenProvider::COOKIE_KEY);
        $currentPath = $mainRequest->getPathInfo();
        $isWishlistUrl = str_starts_with($currentPath, self::ALLOWED_ENDPOINTS_PREFIX);
        if (!$tokenWasGenerated && !$isWishlistUrl) {
            return;
        }

        if ($mainRequest->cookies->has(WishlistTokenProvider::COOKIE_KEY)) {
            return;
        }

        $response = $event->getResponse();
        $wishlistCookieToken = $mainRequest->attributes->get(WishlistTokenProvider::COOKIE_KEY);
        if (null === $wishlistCookieToken || '' === $wishlistCookieToken) {
            return;
        }

        $cookie = new Cookie(WishlistTokenProvider::COOKIE_KEY, $wishlistCookieToken, strtotime('+1 year'));
        $response->headers->setCookie($cookie);

        $mainRequest->attributes->remove(WishlistTokenProvider::COOKIE_KEY);
    }

    private function getMainRequest(): Request
    {
        /** @var ?Request $mainRequest */
        $mainRequest = $this->requestStack->getMainRequest();
        Assert::notNull($mainRequest, 'The class has to be used in HTTP context only');

        return $mainRequest;
    }
}
