<?php

declare(strict_types=1);

namespace Tests\SyliusAcademy\WishlistPlugin\Unit\EventSubscriber;

use PHPUnit\Framework\TestCase;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistInterface;
use SyliusAcademy\WishlistPlugin\EventSubscriber\CreateNewWishlistSubscriber;
use SyliusAcademy\WishlistPlugin\Provider\WishlistProviderInterface;
use SyliusAcademy\WishlistPlugin\Provider\WishlistTokenProvider;
use SyliusAcademy\WishlistPlugin\Provider\WishlistTokenProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class CreateNewWishlistSubscriberTest extends TestCase
{
    private WishlistProviderInterface $wishlistProvider;

    private WishlistTokenProviderInterface $wishlistTokenProvider;

    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->wishlistProvider = self::createMock(WishlistProviderInterface::class);
        $this->wishlistTokenProvider = self::createMock(WishlistTokenProviderInterface::class);
        $this->requestStack = new RequestStack();
    }

    private function createRequestEvent(string $uri, int $type): RequestEvent
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $uri]);
        $this->requestStack->push($request);

        return new RequestEvent(
            self::createMock(HttpKernelInterface::class),
            $request,
            $type,
        );
    }

    private function createResponseEvent(string $uri, int $type): ResponseEvent
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $uri]);
        $this->requestStack->push($request);

        return new ResponseEvent(
            self::createMock(HttpKernelInterface::class),
            $request,
            $type,
            new Response(),
        );
    }

    private function createSubscriber(): CreateNewWishlistSubscriber
    {
        return new CreateNewWishlistSubscriber(
            $this->wishlistProvider,
            $this->wishlistTokenProvider,
            $this->requestStack,
        );
    }

    public function test_does_nothing_on_non_wishlist_url(): void
    {
        $event = $this->createRequestEvent('/some-other-page', HttpKernelInterface::MAIN_REQUEST);

        $this->wishlistProvider->expects($this->never())->method('provide');

        $this->createSubscriber()->onKernelRequest($event);
    }

    public function test_does_nothing_on_sub_request_url(): void
    {
        $event = $this->createRequestEvent('/some-other-page', HttpKernelInterface::SUB_REQUEST);

        $this->wishlistProvider->expects($this->never())->method('provide');

        $this->createSubscriber()->onKernelRequest($event);
    }

    public function test_does_nothing_on_sub_request_url_for_event_kernel_response(): void
    {
        $event = $this->createResponseEvent('/some-other-page', HttpKernelInterface::SUB_REQUEST);

        $this->wishlistProvider->expects($this->never())->method('provide');

        $this->createSubscriber()->onKernelResponse($event);
    }

    /**
     * @dataProvider provideTokenValues
     */
    public function test_sets_cookie_on_response_when_needed(?string $existingCookie, ?string $attributeToken, bool $shouldSetCookie): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/wishlist']);

        if ($existingCookie !== null) {
            $request->cookies->set(WishlistTokenProvider::COOKIE_KEY, $existingCookie);
        }
        if ($attributeToken !== null) {
            $request->attributes->set(WishlistTokenProvider::COOKIE_KEY, $attributeToken);
        }

        $this->requestStack->push($request);

        $response = new Response();
        $event = new ResponseEvent(
            self::createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $this->createSubscriber()->onKernelResponse($event);

        $cookies = $response->headers->getCookies();
        $shouldSetCookie ? $this->assertNotEmpty($cookies) : $this->assertEmpty($cookies);

        if ($shouldSetCookie) {
            $this->assertEquals(WishlistTokenProvider::COOKIE_KEY, $cookies[0]->getName());
            $this->assertEquals($attributeToken, $cookies[0]->getValue());
        }
    }

    public static function provideTokenValues(): array
    {
        return [
            'has cookie already, no set needed' => ['existing_cookie_value', 'new_token', false],
            'no cookie but has attribute token' => [null, 'token_from_attr', true],
            'no cookie and empty token' => [null, null, false],
        ];
    }

    public function test_sets_attribute_token_if_wishlist_has_token(): void
    {
        $event = $this->createRequestEvent('/wishlist', HttpKernelInterface::MAIN_REQUEST);
        $wishlist = self::createMock(WishlistInterface::class);
        $wishlist->method('getWishlistToken')->willReturn('wishlist_token');

        $this->wishlistProvider->method('provide')->willReturn($wishlist);

        $this->createSubscriber()->onKernelRequest($event);

        $this->assertEquals('wishlist_token', $event->getRequest()->attributes->get(WishlistTokenProvider::COOKIE_KEY));
    }

    public function test_generates_token_if_both_cookie_and_wishlist_token_are_null(): void
    {
        $event = $this->createRequestEvent('/wishlist', HttpKernelInterface::MAIN_REQUEST);

        $wishlist = self::createMock(WishlistInterface::class);
        $wishlist->method('getWishlistToken')->willReturn(null);

        $this->wishlistProvider->method('provide')->willReturn($wishlist);
        $this->wishlistTokenProvider->method('provide')->willReturn('generated_token');

        $this->createSubscriber()->onKernelRequest($event);

        $this->assertEquals('generated_token', $event->getRequest()->attributes->get(WishlistTokenProvider::COOKIE_KEY));
    }

    public function test_get_subscribed_events_returns_expected_events(): void
    {
        $expectedEvents = [
            'kernel.request' => [['onKernelRequest', 1]],
            'kernel.response' => [['onKernelResponse', 0]],
        ];

        $this->assertEquals($expectedEvents, CreateNewWishlistSubscriber::getSubscribedEvents());
    }

    public function test_sets_attribute_token_from_wishlist_when_no_cookie_present(): void
    {
        $event = $this->createRequestEvent('/wishlist', HttpKernelInterface::MAIN_REQUEST);

        $event->getRequest()->cookies->set(WishlistTokenProvider::COOKIE_KEY, null);

        $wishlist = self::createMock(WishlistInterface::class);
        $wishlist->method('getWishlistToken')->willReturn('wishlist_token');
        $wishlist->method('getId')->willReturn(1);

        $this->wishlistProvider->method('provide')->willReturn($wishlist);

        $this->createSubscriber()->onKernelRequest($event);

        $this->assertEquals('wishlist_token', $event->getRequest()->attributes->get(WishlistTokenProvider::COOKIE_KEY));
    }

    public function test_does_nothing_when_token_is_missing_or_empty_and_other_page_than_wishlist(): void
    {
        $event = $this->createResponseEvent('/other-page', HttpKernelInterface::MAIN_REQUEST);

        $this->createSubscriber()->onKernelResponse($event);

        $this->assertEmpty($event->getResponse()->headers->getCookies());
    }
}
