<?php

declare(strict_types=1);

namespace SyliusAcademy\WishlistPlugin\Provider\Tests\Helper;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use SyliusAcademy\WishlistPlugin\Helper\FlashHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class FlashHelperTest extends TestCase
{
    private RequestStack $requestStackMock;

    private FlashBagInterface $flashBagMock;

    private Session $sessionMock;

    private FlashHelper $flashHelper;

    protected function setUp(): void
    {
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->flashBagMock = $this->createMock(FlashBagInterface::class);
        $this->sessionMock = $this->createMock(Session::class);

        $this->flashHelper = new FlashHelper($this->requestStackMock);
    }

    public function test_create_message_adds_message_to_flash_bag(): void
    {
        $type = 'success';
        $format = 'Item %s added successfully!';
        $data = ['Product 1'];
        $expectedMessage = 'Item Product 1 added successfully!';

        $this->sessionMock
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($this->flashBagMock);

        $this->flashBagMock
            ->expects($this->once())
            ->method('add')
            ->with($type, $expectedMessage);

        $this->requestStackMock
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($this->sessionMock);

        $this->flashHelper->createMessage($type, $format, ...$data);
    }

    public function test_create_message_throws_exception_for_non_scalar_data(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Data passed to createMessage must be a scalar value or null.');

        $type = 'error';
        $format = 'Invalid data: %s';
        $data = [new \stdClass()];

        $this->flashHelper->createMessage($type, $format, ...$data);
    }

    public function test_create_message_throws_logic_exception_when_session_is_invalid(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The current session must be an instance of "Symfony\\Component\\HttpFoundation\\Session\\Session".');

        $this->requestStackMock
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($this->createMock(SessionInterface::class));

        $this->flashHelper->createMessage('info', 'Some message');
    }
}
