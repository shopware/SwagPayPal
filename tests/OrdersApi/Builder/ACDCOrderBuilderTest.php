<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder;

use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\ACDCOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes\Verification;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\OrdersApi\Builder\Trait\VaultableOrderBuildTrait;

/**
 * @internal
 */
#[Package('checkout')]
class ACDCOrderBuilderTest extends AbstractOrderBuilderTestCase
{
    use VaultableOrderBuildTrait;

    public function testGetOrderUsesScaWhenRequiredByDefault(): void
    {
        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder());

        $order = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $this->createSalesChannelContext(),
            new RequestDataBag(),
        );

        $card = $order->getPaymentSource()?->first(Card::class);
        static::assertInstanceOf(Card::class, $card);
        static::assertSame(Verification::METHOD_SCA_WHEN_REQUIRED, $card->getAttributes()?->getVerification()?->getMethod());
    }

    public function testGetOrderUsesScaAlwaysIfForce3dsEnabled(): void
    {
        $this->systemConfig->set(Settings::ACDC_FORCE_3DS, true);

        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder());

        $order = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $this->createSalesChannelContext(),
            new RequestDataBag(),
        );

        $card = $order->getPaymentSource()?->first(Card::class);
        static::assertInstanceOf(Card::class, $card);
        static::assertSame(Verification::METHOD_SCA_ALWAYS, $card->getAttributes()?->getVerification()?->getMethod());
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
