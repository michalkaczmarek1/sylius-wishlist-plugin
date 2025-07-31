<?php

declare(strict_types=1);

namespace Tests\SyliusAcademy\WishlistPlugin\Unit\Helper;

use PHPUnit\Framework\TestCase;
use SyliusAcademy\WishlistPlugin\Helper\FlashHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

class FlashHelperTest extends TestCase
{
    private RequestStack $requestStack;

    private FlashBagInterface $flashBag;

    protected function setUp(): void
    {
        $this->flashBag = self::createMock(FlashBagInterface::class);
        $this->requestStack = $this->create_request_stack_with_flash_bag($this->flashBag);
    }

    /**
     * @dataProvider flashMessageProvider
     */
    public function test_create_message_adds_flash_entry(string $type, string $format, array $params, string $expectedMessage): void
    {
        $this->flashBag
            ->expects($this->once())
            ->method('add')
            ->with($type, $expectedMessage);

        $flashHelper = new FlashHelper($this->requestStack);
        $flashHelper->createMessage($type, $format, ...$params);
    }

    public static function flashMessageProvider(): array
    {
        return [
            'success_message' => ['success', 'Added product "%s" to cart.', ['Book'], 'Added product "Book" to cart.'],
            'error_message' => ['error', 'Error occurred: %s', ['Out of stock'], 'Error occurred: Out of stock'],
            'info_message_with_number' => ['info', 'Processed %d items.', [5], 'Processed 5 items.'],
            'warning_message_empty_param' => ['warning', 'Something went wrong', [], 'Something went wrong'],
        ];
    }

    private function create_request_stack_with_flash_bag(FlashBagInterface $flashBag): RequestStack
    {
        $session = self::createMock(FlashBagAwareSessionInterface::class);
        $session->method('getFlashBag')->willReturn($flashBag);

        $request = self::createMock(Request::class);
        $request->method('hasSession')->willReturn(true);
        $request->method('getSession')->willReturn($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }
}
