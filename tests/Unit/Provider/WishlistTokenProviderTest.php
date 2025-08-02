<?php

declare(strict_types=1);

namespace Tests\SyliusAcademy\WishlistPlugin\Unit\Provider;

use PHPUnit\Framework\TestCase;
use SyliusAcademy\WishlistPlugin\Provider\WishlistTokenProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class WishlistTokenProviderTest extends TestCase
{
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
    }

    public function test_it_returns_new_token_when_main_request_is_null(): void
    {
        $provider = $this->createProvider();
        $result = $provider->provide();

        $this->assertValidUuid($result);
    }

    public function test_it_returns_token_from_cookie_if_present(): void
    {
        $request = $this->createRequestWithCookie('cookie-token');
        $provider = $this->createProviderWithRequest($request);

        $this->assertSame('cookie-token', $provider->provide());
    }

    public function test_it_returns_token_from_attribute_if_cookie_not_set(): void
    {
        $request = $this->createRequestWithAttribute('attribute-token');
        $provider = $this->createProviderWithRequest($request);

        $this->assertSame('attribute-token', $provider->provide());
    }

    public function test_it_returns_new_token_if_neither_cookie_nor_attribute_exist(): void
    {
        $request = new Request();
        $provider = $this->createProviderWithRequest($request);

        $token = $provider->provide();

        $this->assertValidUuid($token);
    }

    private function createProvider(): WishlistTokenProvider
    {
        return new WishlistTokenProvider($this->requestStack);
    }

    private function createProviderWithRequest(Request $request): WishlistTokenProvider
    {
        $this->requestStack->push($request);

        return $this->createProvider();
    }

    private function createRequestWithCookie(string $token): Request
    {
        $request = new Request();
        $request->cookies->set(WishlistTokenProvider::COOKIE_KEY, $token);

        return $request;
    }

    private function createRequestWithAttribute(string $token): Request
    {
        $request = new Request();
        $request->attributes->set(WishlistTokenProvider::COOKIE_KEY, $token);

        return $request;
    }

    private function assertValidUuid(string $uuid): void
    {
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/',
            $uuid,
        );
    }
}
