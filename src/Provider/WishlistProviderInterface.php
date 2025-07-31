<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Provider;

use Exception;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistInterface;

interface WishlistProviderInterface
{
    /**
     * @throws Exception
     */
    public function provide(): WishlistInterface;
}
