<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Entity\Wishlist;

use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TimestampableInterface;

interface WishlistInterface extends TimestampableInterface, ResourceInterface
{
    public function getWishlistToken(): ?string;

    public function setWishlistToken(?string $wishlistToken): void;

    public function getWishlistProducts();

    public function addWishlistProduct(?WishlistProduct $wishlistProduct);

    public function removeWishlistProduct(?WishlistProduct $wishlistProduct);

    public function getCustomer(): ?ShopUserInterface;

    public function setCustomer(?ShopUserInterface $customer): void;
}
