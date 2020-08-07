<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Plan\BillingCycle\PricingScheme;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class FixedPrice extends PayPalStruct
{
    /**
     * @var string
     */
    protected $value;

    /**
     * @var string
     */
    protected $currencyCode;

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): void
    {
        $this->currencyCode = $currencyCode;
    }
}
