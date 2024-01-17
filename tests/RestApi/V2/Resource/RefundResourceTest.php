<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V2\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\RestApi\V2\Resource\RefundResource;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetRefund;

/**
 * @internal
 */
#[Package('checkout')]
class RefundResourceTest extends TestCase
{
    use ServicesTrait;

    public function testGet(): void
    {
        $refundId = GetRefund::ID;
        $refund = $this->createResource()->get($refundId, TestDefaults::SALES_CHANNEL);

        static::assertSame($refundId, $refund->getId());
        static::assertSame('12.34', $refund->getSellerPayableBreakdown()->getTotalRefundedAmount()->getValue());
    }

    private function createResource(): RefundResource
    {
        return new RefundResource($this->createPayPalClientFactory());
    }
}
