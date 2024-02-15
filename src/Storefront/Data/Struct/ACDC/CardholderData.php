<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Struct\ACDC;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @deprecated tag:v10.0.0 - will be removed without replacement
 */
#[Package('checkout')]
class CardholderData extends Struct
{
    public const CONTINGENCY_SCA_WHEN_REQUIRED = 'SCA_WHEN_REQUIRED';
    public const CONTINGENCY_SCA_ALWAYS = 'SCA_ALWAYS';

    protected string $cardholderName;

    protected BillingAddress $billingAddress;

    protected array $contingencies = [
        self::CONTINGENCY_SCA_ALWAYS,
    ];

    public function getCardholderName(): string
    {
        return $this->cardholderName;
    }

    public function setCardholderName(string $cardholderName): void
    {
        $this->cardholderName = $cardholderName;
    }

    public function getBillingAddress(): BillingAddress
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(BillingAddress $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function getContingencies(): array
    {
        return $this->contingencies;
    }

    public function setContingencies(array $contingencies): void
    {
        $this->contingencies = $contingencies;
    }
}
