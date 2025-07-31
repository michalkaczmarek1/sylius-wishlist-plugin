<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Helper;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

final readonly class FlashHelper implements FlashHelperInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function createMessage(string $type, string $format, ...$data): void
    {
        $this->getFlashBag()->add($type, sprintf($format, ...$data));
    }

    private function getFlashBag(): FlashBagInterface
    {
        return $this->requestStack->getSession()->getFlashBag();
    }
}
