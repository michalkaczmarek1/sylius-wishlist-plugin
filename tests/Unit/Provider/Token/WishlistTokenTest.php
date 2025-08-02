<?php

declare(strict_types=1);

namespace Tests\SyliusAcademy\WishlistPlugin\Unit\Provider\Token;

use PHPUnit\Framework\TestCase;
use SyliusAcademy\WishlistPlugin\Provider\Token\WishlistToken;

final class WishlistTokenTest extends TestCase
{
    public function test_it_returns_token_value(): void
    {
        $token = new WishlistToken('test-token');
        $this->assertSame('test-token', $token->getValue());
    }

    public function test_it_accepts_empty_string(): void
    {
        $token = new WishlistToken('');
        $this->assertSame('', $token->getValue());
    }

    public function test_it_accepts_uuid(): void
    {
        $uuid = '123e4567-e89b-12d3-a456-426614174000';
        $token = new WishlistToken($uuid);
        $this->assertSame($uuid, $token->getValue());
    }

    public function test_it_handles_special_characters(): void
    {
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        $token = new WishlistToken($special);
        $this->assertSame($special, $token->getValue());
    }
}
