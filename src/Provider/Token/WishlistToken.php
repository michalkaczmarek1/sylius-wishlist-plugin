<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Provider\Token;

use Ramsey\Uuid\Uuid;

final class WishlistToken implements WishlistTokenInterface
{
    public function __construct(
        private ?string $value = null,
    ) {
        if (null === $value) {
            $this->value = $this->generate();
        } else {
            $this->setValue($value);
        }
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->getValue() ?? '';
    }

    private function generate(): string
    {
        return Uuid::uuid4()->toString();
    }
}
