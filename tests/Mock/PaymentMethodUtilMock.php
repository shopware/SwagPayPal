<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock;

use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Test\Mock\Repositories\PaymentMethodRepoMock;
use Swag\PayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Util\PaymentMethodUtil;

class PaymentMethodUtilMock extends PaymentMethodUtil
{
    public const PAYMENT_METHOD_ID = 'cfbd5018d38d41a8adcae0d94fc8bddc';

    public function __construct()
    {
        parent::__construct(new PaymentMethodRepoMock(), new SalesChannelRepoMock());
    }

    public function getPayPalPaymentMethodId(Context $context): string
    {
        return self::PAYMENT_METHOD_ID;
    }

    public function isPaypalPaymentMethodInSalesChannel(SalesChannelContext $salesChannelContext): bool
    {
        $paymentMethodCollection = $salesChannelContext->getSalesChannel()->getPaymentMethods();
        if (!$paymentMethodCollection) {
            return false;
        }

        return $paymentMethodCollection->has(self::PAYMENT_METHOD_ID);
    }
}
