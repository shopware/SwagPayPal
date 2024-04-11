<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Availability;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('checkout')]
class AvailabilityContext extends Struct
{
    protected string $billingCountryCode;

    protected string $currencyCode;

    protected float $totalAmount;

    protected bool $subscription;

    protected string $salesChannelId;

    protected bool $hasDigitalProducts;

    public function getBillingCountryCode(): string
    {
        return $this->billingCountryCode;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function isSubscription(): bool
    {
        return $this->subscription;
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function hasDigitalProducts(): bool
    {
        return $this->hasDigitalProducts;
    }
}
