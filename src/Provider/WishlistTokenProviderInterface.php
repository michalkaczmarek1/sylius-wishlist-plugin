<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Provider;

interface WishlistTokenProviderInterface
{
    public function provide(): string;
}
