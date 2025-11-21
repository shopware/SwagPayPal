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
class AbstractScriptData extends Struct
{
    public const PAGE_TYPE_CART = 'cart';
    public const PAGE_TYPE_CHECKOUT = 'checkout';
    public const PAGE_TYPE_HOME = 'home';
    public const PAGE_TYPE_MINI_CART = 'mini-cart';
    public const PAGE_TYPE_PRODUCT_DETAILS = 'product-details';
    public const PAGE_TYPE_PRODUCT_LISTING = 'product-listing';
    public const PAGE_TYPE_SEARCH_RESULTS = 'search-results';

    protected bool $_v6Enabled;

    /**
     * @deprecated tag:v11.0.0 - Will be removed and is replaced by {@see self::clientToken}
     */
    protected string $clientId;

    /**
     * @deprecated tag:v11.0.0 - Will be removed and is replaced by {@see self::clientToken}
     */
    protected string $merchantPayerId;

    protected string $partnerAttributionId;

    protected string $languageIso;

    protected string $currency;

    protected string $intent;

    protected string $clientToken;

    protected string $environment;

    protected ?string $pageType = null;

    /**
     * @deprecated tag:v11.0.0 - Will be removed
     */
    public function isV6Enabled(): bool
    {
        return $this->_v6Enabled;
    }

    /**
     * @deprecated tag:v11.0.0 - Will be removed and is replaced by {@see self::clientToken}
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @deprecated tag:v11.0.0 - Will be removed and is replaced by {@see self::clientToken}
     */
    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * @deprecated tag:v11.0.0 - Will be removed and is replaced by {@see self::clientToken}
     */
    public function getMerchantPayerId(): string
    {
        return $this->merchantPayerId;
    }

    /**
     * @deprecated tag:v11.0.0 - Will be removed and is replaced by {@see self::clientToken}
     */
    public function setMerchantPayerId(string $merchantPayerId): void
    {
        $this->merchantPayerId = $merchantPayerId;
    }

    public function getPartnerAttributionId(): string
    {
        return $this->partnerAttributionId;
    }

    public function setPartnerAttributionId(string $partnerAttributionId): void
    {
        $this->partnerAttributionId = $partnerAttributionId;
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

    public function getClientToken(): string
    {
        return $this->clientToken;
    }

    public function setClientToken(string $clientToken): void
    {
        $this->clientToken = $clientToken;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function setEnvironment(string $environment): void
    {
        $this->environment = $environment;
    }

    public function getPageType(): ?string
    {
        return $this->pageType;
    }

    public function setPageType(?string $pageType): void
    {
        $this->pageType = $pageType;
    }
}
