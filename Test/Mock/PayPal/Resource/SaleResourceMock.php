<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\PayPal\Resource;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Refund;
use SwagPayPal\PayPal\Resource\SaleResource;

class SaleResourceMock extends SaleResource
{
    public function refund(string $paymentId, Refund $refund, Context $context): Refund
    {
        return $refund;
    }
}
