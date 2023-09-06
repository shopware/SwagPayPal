<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Data\Struct;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('checkout')]
class FundingEligibilityData extends Struct
{
    protected string $clientId;

    protected string $merchantPayerId;

    protected string $languageIso;

    protected string $currency;

    protected string $intent;

    /**
     * @var string[]
     */
    protected array $filteredPaymentMethods;

    protected string $methodEligibilityUrl;

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getMerchantPayerId(): string
    {
        return $this->merchantPayerId;
    }

    public function setMerchantPayerId(string $merchantPayerId): void
    {
        $this->merchantPayerId = $merchantPayerId;
    }

    public function getLanguageIso(): string
    {
        return $this->languageIso;
    }

    public function setLanguageIso(string $languageIso): void
    {
        $this->languageIso = $languageIso;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getIntent(): string
    {
        return $this->intent;
    }

    public function setIntent(string $intent): void
    {
        $this->intent = $intent;
    }

    public function getFilteredPaymentMethods(): array
    {
        return $this->filteredPaymentMethods;
    }

    public function setFilteredPaymentMethods(array $filteredPaymentMethods): void
    {
        $this->filteredPaymentMethods = $filteredPaymentMethods;
    }

    public function getMethodEligibilityUrl(): string
    {
        return $this->methodEligibilityUrl;
    }

    public function setMethodEligibilityUrl(string $methodEligibilityUrl): void
    {
        $this->methodEligibilityUrl = $methodEligibilityUrl;
    }
}
