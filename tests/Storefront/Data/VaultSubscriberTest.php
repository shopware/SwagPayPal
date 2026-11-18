<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Data;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Generator;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPage;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Storefront\Data\Service\SavedPaymentMethodDataService;
use Swag\PayPal\Storefront\Data\Service\VaultDataService;
use Swag\PayPal\Storefront\Data\Struct\SavedPaymentMethodData;
use Swag\PayPal\Storefront\Data\Struct\VaultData;
use Swag\PayPal\Storefront\Data\VaultSubscriber;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(VaultSubscriber::class)]
class VaultSubscriberTest extends TestCase
{
    private SettingsValidationServiceInterface&MockObject $settingsValidationService;

    private VaultDataService&MockObject $vaultDataService;

    private SavedPaymentMethodDataService&MockObject $savedPaymentMethodDataService;

    private VaultSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->settingsValidationService = $this->createMock(SettingsValidationServiceInterface::class);
        $this->vaultDataService = $this->createMock(VaultDataService::class);
        $this->savedPaymentMethodDataService = $this->createMock(SavedPaymentMethodDataService::class);

        $this->subscriber = new VaultSubscriber(
            $this->settingsValidationService,
            $this->vaultDataService,
            $this->savedPaymentMethodDataService,
        );
    }

    public function testAddsSavedPaymentMethodOnConfirmPage(): void
    {
        $cart = new Cart(Uuid::randomHex());
        $page = new CheckoutConfirmPage();
        $page->setCart($cart);
        $event = new CheckoutConfirmPageLoadedEvent($page, Generator::generateSalesChannelContext(), new Request());

        $vaultData = $this->createVaultData('buyer@example.com');
        $this->vaultDataService->method('buildData')->willReturn($vaultData);

        $savedPaymentMethod = new SavedPaymentMethodData();
        $this->savedPaymentMethodDataService
            ->expects($this->once())
            ->method('buildCheckoutData')
            ->with($event->getSalesChannelContext(), $cart, null)
            ->willReturn($savedPaymentMethod);

        $this->subscriber->addVaultData($event);

        static::assertSame($vaultData, $page->getExtension(VaultSubscriber::VAULT_EXTENSION));
        static::assertSame($savedPaymentMethod, $vaultData->getSavedPaymentMethod());
    }

    public function testAddsSavedPaymentMethodOnOrderEditPage(): void
    {
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $page = new AccountEditOrderPage();
        $page->setOrder($order);
        $event = new AccountEditOrderPageLoadedEvent($page, Generator::generateSalesChannelContext(), new Request());

        $vaultData = $this->createVaultData('buyer@example.com');
        $this->vaultDataService->method('buildData')->willReturn($vaultData);

        $this->savedPaymentMethodDataService
            ->expects($this->once())
            ->method('buildCheckoutData')
            ->with($event->getSalesChannelContext(), null, $order)
            ->willReturn(null);

        $this->subscriber->addVaultData($event);

        static::assertSame($vaultData, $page->getExtension(VaultSubscriber::VAULT_EXTENSION));
        static::assertNull($vaultData->getSavedPaymentMethod());
    }

    public function testWithoutSavedVault(): void
    {
        $page = new CheckoutConfirmPage();
        $page->setCart(new Cart(Uuid::randomHex()));
        $event = new CheckoutConfirmPageLoadedEvent($page, Generator::generateSalesChannelContext(), new Request());

        $vaultData = $this->createVaultData(null);
        $this->vaultDataService->method('buildData')->willReturn($vaultData);
        $this->savedPaymentMethodDataService->expects($this->never())->method('buildCheckoutData');

        $this->subscriber->addVaultData($event);

        static::assertSame($vaultData, $page->getExtension(VaultSubscriber::VAULT_EXTENSION));
        static::assertNull($vaultData->getSavedPaymentMethod());
    }

    public function testWithInvalidSettings(): void
    {
        $page = new CheckoutConfirmPage();
        $event = new CheckoutConfirmPageLoadedEvent($page, Generator::generateSalesChannelContext(), new Request());

        $this->settingsValidationService
            ->method('validate')
            ->willThrowException(new PayPalSettingsInvalidException('clientId'));
        $this->vaultDataService->expects($this->never())->method('buildData');

        $this->subscriber->addVaultData($event);

        static::assertFalse($page->hasExtension(VaultSubscriber::VAULT_EXTENSION));
    }

    private function createVaultData(?string $identifier): VaultData
    {
        $vaultData = new VaultData();
        $vaultData->setIdentifier($identifier);

        return $vaultData;
    }
}
