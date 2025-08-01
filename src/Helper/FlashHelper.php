<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Helper;

use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;

final readonly class FlashHelper implements FlashHelperInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function createMessage(string $type, string $format, mixed ...$data): void
    {
        $preparedData = array_map(function ($value) {
            if (!is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException('Data passed to createMessage must be a scalar value or null.');
            }

            return $value;
        }, $data);

        $this->getFlashBag()->add($type, sprintf($format, ...$preparedData));
    }

    private function getFlashBag(): FlashBagInterface
    {
        $session = $this->requestStack->getSession();

        if (!$session instanceof Session) {
            throw new LogicException('The current session must be an instance of "Symfony\Component\HttpFoundation\Session\Session".');
        }

        return $session->getFlashBag();
    }
}
