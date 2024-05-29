<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\APM;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Sofort;

/**
 * @deprecated tag:v10.0.0 - Will be removed.
 */
class SofortOrderBuilder extends AbstractAPMOrderBuilder
{
    protected function buildPaymentSource(
        AsyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        RequestDataBag $requestDataBag,
        PaymentSource $paymentSource
    ): void {
        $sourceElement = new Sofort();
        $this->fillPaymentSource($paymentTransaction->getOrder(), $sourceElement);

        $paymentSource->setSofort($sourceElement);
    }
}
