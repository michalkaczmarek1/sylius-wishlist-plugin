<?php

declare(strict_types=1);

namespace Tests\SyliusAcademy\WishlistPlugin\Unit\EventSubscriber;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use SyliusAcademy\WishlistPlugin\EventSubscriber\AddWishlistToMenuMyAccountSubscriber;

final class AddWishlistToMenuMyAccountSubscriberTest extends TestCase
{
    public function test_event_subscription_is_correct(): void
    {
        $events = AddWishlistToMenuMyAccountSubscriber::getSubscribedEvents();

        self::assertArrayHasKey('sylius.menu.shop.account', $events);
        self::assertSame('onAddMenuItem', $events['sylius.menu.shop.account']);
    }

    public function test_menu_item_is_added_correctly(): void
    {
        $menu = $this->createMock(ItemInterface::class);
        $child = $this->createMock(ItemInterface::class);

        $menu->expects(self::once())
            ->method('addChild')
            ->with('wishlist_products_index', ['route' => 'sylius_academy_wishlist_shop_account_wishlist_product_index'])
            ->willReturn($child);

        $child->expects(self::once())
            ->method('setLabel')
            ->with('sylius_academy_wishlist_plugin.menu.shop.account.wishlist_products_index')
            ->willReturnSelf();

        $child->expects(self::once())
            ->method('setLabelAttribute')
            ->with('icon', 'tabler:heart-filled')
            ->willReturnSelf();

        $event = $this->createMock(MenuBuilderEvent::class);
        $event->method('getMenu')->willReturn($menu);

        (new AddWishlistToMenuMyAccountSubscriber())->onAddMenuItem($event);
    }
}
