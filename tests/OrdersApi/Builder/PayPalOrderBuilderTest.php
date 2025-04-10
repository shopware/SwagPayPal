<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder;

use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Paypal;
use Swag\PayPal\Test\OrdersApi\Builder\Trait\VaultableOrderBuildTrait;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalOrderBuilderTest extends AbstractOrderBuilderTestCase
{
    use VaultableOrderBuildTrait;

    public function testGetOrderNoBillingAddress(): void
    {
        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder());
        $salesChannelContext = $this->createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $paymentTransaction->getOrder()->assign(['billingAddress' => null]);
        $customer->assign(['activeBillingAddress' => null, 'defaultBillingAddress' => null]);

        $this->expectException(OrderException::class);
        $this->expectExceptionMessage('The required association "billingAddress" is missing .');
        $this->getBuilder()->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag(),
        );
    }

    protected function getBuilder(): AbstractOrderBuilder
    {
        return new PayPalOrderBuilder(
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
        return Paypal::class;
    }
}
