<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Resource;

use Shopware\Core\Framework\Context;
use Swag\PayPal\PayPal\Api\Refund;
use Swag\PayPal\PayPal\Resource\SaleResource;

class SaleResourceMock extends SaleResource
{
    public function refund(string $saleId, Refund $refund, Context $context): Refund
    {
        return $refund;
    }
}
