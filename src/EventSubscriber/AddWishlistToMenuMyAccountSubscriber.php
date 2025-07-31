<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\EventSubscriber;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AddWishlistToMenuMyAccountSubscriber implements EventSubscriberInterface
{
    public function onAddMenuItem(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();
        $menu
            ->addChild('wishlist_products_index', ['route' => 'sylius_academy_wishlist_shop_account_wishlist_product_index'])
            ->setLabel('sylius_academy_wishlist_plugin.menu.shop.account.wishlist_products_index')
            ->setLabelAttribute('icon', 'tabler:heart-filled');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.menu.shop.account' => 'onAddMenuItem',
        ];
    }
}
