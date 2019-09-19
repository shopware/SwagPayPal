<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\PayPal\Resource;

use Swag\PayPal\PayPal\Api\Refund;
use Swag\PayPal\PayPal\Resource\SaleResource;

class SaleResourceMock extends SaleResource
{
    public function refund(string $saleId, Refund $refund, string $salesChannelId): Refund
    {
        return $refund;
    }
}
