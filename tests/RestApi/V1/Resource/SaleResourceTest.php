<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\RestApi\V1\Api\Refund;
use Swag\PayPal\RestApi\V1\PaymentStatusV1;
use Swag\PayPal\RestApi\V1\Resource\SaleResource;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceSaleResponseFixture;

/**
 * @internal
 */
#[Package('checkout')]
class SaleResourceTest extends TestCase
{
    use ServicesTrait;

    public function testGet(): void
    {
        $saleResponse = $this->createSaleResource()->get(
            'saleId',
            TestDefaults::SALES_CHANNEL
        );

        $sale = \json_encode($saleResponse);
        static::assertNotFalse($sale);

        $saleArray = \json_decode($sale, true);

        static::assertSame(GetResourceSaleResponseFixture::ID, $saleArray['id']);
    }

    public function testRefund(): void
    {
        $refund = new Refund();
        $refundResponse = $this->createSaleResource()->refund(
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID,
            $refund,
            TestDefaults::SALES_CHANNEL
        );

        static::assertSame(PaymentStatusV1::PAYMENT_COMPLETED, $refundResponse->getState());
    }

    private function createSaleResource(): SaleResource
    {
        return new SaleResource(
            $this->createPayPalClientFactory()
        );
    }
}
