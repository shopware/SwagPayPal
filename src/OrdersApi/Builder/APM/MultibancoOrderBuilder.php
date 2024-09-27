<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\APM;

use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Multibanco;

#[Package('checkout')]
class MultibancoOrderBuilder extends AbstractAPMOrderBuilder
{
    protected function buildPaymentSource(
        SyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag,
        PaymentSource $paymentSource,
    ): void {
        $sourceElement = new Multibanco();
        $this->fillPaymentSource($paymentTransaction, $salesChannelContext, $sourceElement);

        $paymentSource->setMultibanco($sourceElement);
    }
}
