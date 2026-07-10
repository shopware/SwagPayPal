<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder;

use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Card;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\Attributes\Verification;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\ACDCOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\OrdersApi\Builder\Trait\VaultableOrderBuildTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class ACDCOrderBuilderTest extends AbstractOrderBuilderTestCase
{
    use VaultableOrderBuildTrait;

    public function testGetOrderUsesScaWhenRequiredByDefault(): void
    {
        $orderTransaction = $this->createOrderTransaction();
        $order = $this->createOrder();
        $paymentTransaction = new PaymentTransactionStruct($orderTransaction->getId());

        $paypalOrder = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $orderTransaction,
            $order,
            Context::createDefaultContext(),
            new Request(),
        );

        $card = $paypalOrder->getPaymentSource()?->first(Card::class);
        static::assertSame(Verification::METHOD_SCA_WHEN_REQUIRED, $card?->getAttributes()?->getVerification()?->getMethod());
    }

    public function testGetOrderUsesScaAlwaysIfForce3dsEnabled(): void
    {
        $this->systemConfig->set(Settings::ACDC_FORCE_3DS, true);
        $orderTransaction = $this->createOrderTransaction();
        $order = $this->createOrder();
        $paymentTransaction = new PaymentTransactionStruct($orderTransaction->getId());

        $paypalOrder = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $orderTransaction,
            $order,
            Context::createDefaultContext(),
            new Request(),
        );

        $card = $paypalOrder->getPaymentSource()?->first(Card::class);
        static::assertSame(Verification::METHOD_SCA_ALWAYS, $card?->getAttributes()?->getVerification()?->getMethod());
    }

    protected function getBuilder(): AbstractOrderBuilder
    {
        return new ACDCOrderBuilder(
            $this->systemConfig,
            $this->purchaseUnitProvider,
            new AddressProvider(),
            $this->localeCodeProvider,
            $this->itemListProvider,
            $this->vaultTokenService,
        );
    }

    protected function getPaymentSourceClass(): string
    {
        return Card::class;
    }
}
