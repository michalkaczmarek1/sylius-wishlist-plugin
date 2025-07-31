<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Helper;

interface FlashHelperInterface
{
    public function createMessage(string $type, string $format, ...$data): void;
}
