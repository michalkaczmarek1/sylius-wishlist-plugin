<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Entity\Wishlist;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TimestampableInterface;

interface WishlistInterface extends TimestampableInterface, ResourceInterface
{
    public function getWishlistToken(): ?string;

    public function setWishlistToken(?string $wishlistToken): void;

    /**
     * @return Collection<int, WishlistProductInterface|null>
     */
    public function getWishlistProducts(): Collection;

    public function addWishlistProduct(?WishlistProductInterface $wishlistProduct): void;

    public function removeWishlistProduct(?WishlistProduct $wishlistProduct): void;

    public function getCustomer(): ?ShopUserInterface;

    public function setCustomer(?ShopUserInterface $customer): void;
}
