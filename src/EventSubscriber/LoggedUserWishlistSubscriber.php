<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Bundle\ShopBundle\SectionResolver\ShopSection;
use Sylius\Bundle\UserBundle\Event\UserEvent;
use Sylius\Bundle\UserBundle\UserEvents;
use Sylius\Component\Core\Model\ShopUserInterface;
use SyliusAcademy\WishlistPlugin\Provider\WishlistProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\SecurityEvents;

final readonly class LoggedUserWishlistSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SectionProviderInterface $uriBasedSectionContext,
        private WishlistProviderInterface $wishlistProvider,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::SECURITY_IMPLICIT_LOGIN => 'onImplicitLogin',
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    /**
     * @throws \Exception
     */
    public function onImplicitLogin(UserEvent $event): void
    {
        if (!$this->uriBasedSectionContext->getSection() instanceof ShopSection) {
            return;
        }

        $user = $event->getUser();
        if (!$user instanceof ShopUserInterface) {
            return;
        }

        $this->saveWishlistsForLoggedUser($user);
    }

    /**
     * @throws \Exception
     */
    public function onInteractiveLogin(UserEvent $userEvent): void
    {
        $section = $this->uriBasedSectionContext->getSection();
        if (!$section instanceof ShopSection) {
            return;
        }

        $user = $userEvent->getUser();
        if (!$user instanceof ShopUserInterface) {
            return;
        }

        $this->saveWishlistsForLoggedUser($user);
    }

    /**
     * @throws \Exception
     */
    private function saveWishlistsForLoggedUser(ShopUserInterface $user): void
    {
        $wishlist = $this->wishlistProvider->provide();

        if (null === $wishlist->getId()) {
            return;
        }

        /** @var ?ShopUserInterface $wishlistShopUser */
        $wishlistShopUser = $wishlist->getCustomer();

        if (null !== $wishlistShopUser && $wishlistShopUser->getId() === $user->getId()) {
            return;
        }

        $wishlist->setCustomer($user);
        $this->entityManager->flush();
    }
}
