<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Repository;

use Doctrine\ORM\QueryBuilder;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistInterface;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistProductInterface;

interface WishlistProductRepositoryInterface extends RepositoryInterface
{
    public function findByWishlistAndToken(WishlistInterface $wishlist): QueryBuilder;

    public function findOneByIdAndCustomer(string $id, CustomerInterface $customer): ?WishlistProductInterface;

    public function findOneByIdAndWishlist(string $id, WishlistInterface $wishlist): ?WishlistProductInterface;
}
