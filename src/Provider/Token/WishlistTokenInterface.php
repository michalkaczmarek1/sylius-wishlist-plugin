<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Provider\Token;

interface WishlistTokenInterface
{
    public function getValue(): ?string;

    public function setValue(?string $value): void;

    public function __toString(): string;
}
