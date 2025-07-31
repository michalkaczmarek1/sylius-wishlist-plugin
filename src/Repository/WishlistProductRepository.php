<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Repository;

use Doctrine\ORM\QueryBuilder;
use Exception;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\CustomerInterface;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistInterface;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistProductInterface;

class WishlistProductRepository extends EntityRepository implements WishlistProductRepositoryInterface
{
    public function findByWishlistAndToken(WishlistInterface $wishlist): QueryBuilder
    {
        $qb = $this->createQueryBuilder('wp');
        if (!$wishlist->getId()) {
            return $qb
                ->andWhere('wp.wishlist = :wishlist')
                ->setParameter('wishlist', null);
        }

        $qb
            ->andWhere('wp.wishlist = :wishlist')
            ->setParameter('wishlist', $wishlist);

        return $qb;
    }

    public function findOneByIdAndCustomer(string $id, CustomerInterface $customer): ?WishlistProductInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            throw new Exception(sprintf('Id %d is wrong. Must be positive', $id));
        }

        return $this->createQueryBuilder('wp')
            ->join('wp.wishlist', 'w')
            ->andWhere('w.customer = :customer')
            ->andWhere('wp.id = :id')
            ->setParameter('customer', $customer)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByIdAndWishlist(string $id, WishlistInterface $wishlist): ?WishlistProductInterface
    {
        $id = (int) $id;
        if ($id <= 0) {
            throw new Exception(sprintf('Id %d is wrong. Must be positive', $id));
        }

        return $this->createQueryBuilder('wp')
            ->andWhere('wp.id = :id')
            ->andWhere('wp.wishlist = :wishlist')
            ->setParameter('id', $id)
            ->setParameter('wishlist', $wishlist)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
