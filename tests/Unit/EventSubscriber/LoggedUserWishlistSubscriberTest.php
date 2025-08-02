<?php

declare(strict_types=1);

namespace Tests\SyliusAcademy\WishlistPlugin\Unit\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Bundle\ShopBundle\SectionResolver\ShopSection;
use Sylius\Bundle\UserBundle\Event\UserEvent;
use Sylius\Bundle\UserBundle\UserEvents;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\User\Model\UserInterface;
use SyliusAcademy\WishlistPlugin\Entity\Wishlist\WishlistInterface;
use SyliusAcademy\WishlistPlugin\EventSubscriber\LoggedUserWishlistSubscriber;
use SyliusAcademy\WishlistPlugin\Provider\WishlistProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

final class LoggedUserWishlistSubscriberTest extends TestCase
{
    private WishlistProviderInterface $wishlistProvider;

    private EntityManagerInterface $entityManager;

    private SectionProviderInterface $sectionProvider;

    private InteractiveLoginEvent $event;

    protected function setUp(): void
    {
        $this->wishlistProvider = self::createMock(WishlistProviderInterface::class);
        $this->entityManager = self::createMock(EntityManagerInterface::class);
        $this->sectionProvider = self::createMock(SectionProviderInterface::class);

        $user = self::createMock(UserInterface::class);
        $token = self::createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->event = new InteractiveLoginEvent(
            self::createMock(Request::class),
            $token,
        );
    }

    public function test_get_subscribed_events_returns_expected_events(): void
    {
        $expected = [
            UserEvents::SECURITY_IMPLICIT_LOGIN => 'onImplicitLogin',
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];

        $this->assertEquals($expected, LoggedUserWishlistSubscriber::getSubscribedEvents());
    }

    public function test_on_implicit_login_skips_if_section_not_shop(): void
    {
        $this->sectionProvider->method('getSection')->willReturn(null);
        $subscriber = $this->createSubscriber();
        $event = self::createMock(UserEvent::class);

        $this->wishlistProvider->expects($this->never())->method('provide');
        $this->entityManager->expects($this->never())->method('flush');

        $subscriber->onImplicitLogin($event);
    }

    public function test_on_implicit_login_skips_if_not_shop_user(): void
    {
        $this->sectionProvider->method('getSection')->willReturn(new ShopSection());
        $subscriber = $this->createSubscriber();

        $event = self::createMock(UserEvent::class);
        $event->method('getUser')->willReturn(self::createMock(UserInterface::class));

        $this->wishlistProvider->expects($this->never())->method('provide');
        $this->entityManager->expects($this->never())->method('flush');

        $subscriber->onImplicitLogin($event);
    }

    public function test_on_implicit_login_saves_wishlist(): void
    {
        $this->sectionProvider->method('getSection')->willReturn(new ShopSection());
        $subscriber = $this->createSubscriber();

        $shopUser = self::createMock(ShopUserInterface::class);
        $event = self::createMock(UserEvent::class);
        $event->method('getUser')->willReturn($shopUser);

        $wishlist = self::createMock(WishlistInterface::class);
        $wishlist->method('getId')->willReturn(1);

        $this->wishlistProvider->expects($this->once())->method('provide')->willReturn($wishlist);
        $wishlist->expects($this->once())->method('setCustomer')->with($shopUser);
        $this->entityManager->expects($this->once())->method('flush');

        $subscriber->onImplicitLogin($event);
    }

    public function test_on_interactive_login_skips_if_section_not_shop(): void
    {
        $this->sectionProvider->method('getSection')->willReturn(null);
        $subscriber = $this->createSubscriber();

        $this->wishlistProvider->expects($this->never())->method('provide');
        $this->entityManager->expects($this->never())->method('flush');

        $subscriber->onInteractiveLogin($this->event);
    }

    public function test_on_interactive_login_skips_if_not_shop_user(): void
    {
        $this->sectionProvider->method('getSection')->willReturn(new ShopSection());
        $subscriber = $this->createSubscriber();

//        $this->event->getAuthenticationToken()->getUser();

        $this->wishlistProvider->expects($this->never())->method('provide');
        $this->entityManager->expects($this->never())->method('flush');

        $subscriber->onInteractiveLogin($this->event);
    }

    #[DataProvider('userProvider')]
    public function test_on_interactive_login_saves_wishlist(
        ?int $wishlistId,
        ?int $userId,
        bool $expectSetCustomer,
        bool $expectFlush,
    ): void {
        $this->sectionProvider->method('getSection')->willReturn(new ShopSection());
        $subscriber = $this->createSubscriber();

        $shopUser = self::createMock(ShopUserInterface::class);
        $shopUser->method('getId')->willReturn($userId);
//        $event = self::createMock(UserEvent::class);
//        $event->method('getUser')->willReturn($shopUser);
        $token = self::createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($shopUser);
        $this->event = new InteractiveLoginEvent(
            self::createMock(Request::class),
            $token,
        );

        $wishlist = self::createMock(WishlistInterface::class);
        $wishlist->method('getId')->willReturn($wishlistId);
        $wishlist->method('getCustomer')->willReturn($userId === 1 ? $shopUser : null);

        $this->wishlistProvider->expects($this->once())->method('provide')->willReturn($wishlist);

        if ($expectSetCustomer) {
            $wishlist->expects($this->once())->method('setCustomer')->with($shopUser);
        } else {
            $wishlist->expects($this->never())->method('setCustomer');
        }

        if ($expectFlush) {
            $this->entityManager->expects($this->once())->method('flush');
        } else {
            $this->entityManager->expects($this->never())->method('flush');
        }

        $subscriber->onInteractiveLogin($this->event);
    }

    public static function userProvider(): array
    {
        return [
            'wishlist with id and different user' => [1, 99, true, true],
            'wishlist with id and same user' => [1, 1, false, false],
            'wishlist without id' => [null, 1, false, false],
        ];
    }

    private function createSubscriber(): LoggedUserWishlistSubscriber
    {
        return new LoggedUserWishlistSubscriber(
            $this->sectionProvider,
            $this->wishlistProvider,
            $this->entityManager,
        );
    }
}
