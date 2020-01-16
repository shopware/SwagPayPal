<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting;

use Shopware\Core\Framework\Struct\Struct;
use Swag\PayPal\PayPal\Api\Payment\ApplicationContext;
use Swag\PayPal\PayPal\PaymentIntent;

class SwagPayPalSettingStruct extends Struct
{
    public const MERCHANT_LOCATION_GERMANY = 'germany';
    public const MERCHANT_LOCATION_OTHER = 'other';
    private const VALID_MERCHANT_LOCATIONS = [
        self::MERCHANT_LOCATION_GERMANY,
        self::MERCHANT_LOCATION_OTHER,
    ];

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * @var bool
     */
    protected $sandbox = false;

    /**
     * @var string
     */
    protected $intent = PaymentIntent::SALE;

    /**
     * @var bool
     */
    protected $submitCart = false;

    /**
     * @var string|null
     */
    protected $webhookId;

    /**
     * @var string|null
     */
    protected $webhookExecuteToken;

    /**
     * @var string|null
     */
    protected $brandName;

    /**
     * @var string
     */
    protected $landingPage = ApplicationContext::LANDING_PAGE_TYPE_LOGIN;

    /**
     * @var bool
     */
    protected $sendOrderNumber = false;

    /**
     * @var string|null
     */
    protected $orderNumberPrefix;

    /**
     * @var string
     */
    protected $merchantLocation = self::MERCHANT_LOCATION_GERMANY;

    /**
     * @var bool
     */
    protected $ecsDetailEnabled = true;

    /**
     * @var bool
     */
    protected $ecsCartEnabled = true;

    /**
     * @var bool
     */
    protected $ecsOffCanvasEnabled = true;

    /**
     * @var bool
     */
    protected $ecsLoginEnabled = true;

    /**
     * @var bool
     */
    protected $ecsListingEnabled = true;

    /**
     * @var string
     */
    protected $ecsButtonColor = 'gold';

    /**
     * @var string
     */
    protected $ecsButtonShape = 'rect';

    /**
     * @var bool
     */
    protected $ecsSubmitCart = false;

    /**
     * @var string|null
     */
    protected $ecsButtonLanguageIso;

    /**
     * @var bool
     */
    protected $spbCheckoutEnabled = true;

    /**
     * @var bool
     */
    protected $spbAlternativePaymentMethodsEnabled = true;

    /**
     * @var string
     */
    protected $spbButtonColor = 'gold';

    /**
     * @var string
     */
    protected $spbButtonShape = 'rect';

    /**
     * @var string|null
     */
    protected $spbButtonLanguageIso;

    /**
     * @var bool
     */
    protected $plusCheckoutEnabled = false;

    /**
     * @var string|null
     */
    protected $plusOverwritePaymentName;

    /**
     * @var string|null
     */
    protected $plusExtendPaymentDescription;

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    public function getSandbox(): bool
    {
        return $this->sandbox;
    }

    public function setSandbox(bool $sandbox): void
    {
        $this->sandbox = $sandbox;
    }

    public function getIntent(): string
    {
        return $this->intent;
    }

    public function setIntent(string $intent): void
    {
        $this->intent = $intent;
    }

    public function setSubmitCart(bool $submitCart): void
    {
        $this->submitCart = $submitCart;
    }

    public function getSubmitCart(): bool
    {
        return $this->submitCart;
    }

    public function getWebhookId(): ?string
    {
        return $this->webhookId;
    }

    public function setWebhookId(string $webhookId): void
    {
        $this->webhookId = $webhookId;
    }

    public function getWebhookExecuteToken(): ?string
    {
        return $this->webhookExecuteToken;
    }

    public function setWebhookExecuteToken(string $webhookExecuteToken): void
    {
        $this->webhookExecuteToken = $webhookExecuteToken;
    }

    public function getBrandName(): ?string
    {
        return $this->brandName;
    }

    public function setBrandName(string $brandName): void
    {
        $this->brandName = $brandName;
    }

    public function getLandingPage(): string
    {
        return $this->landingPage;
    }

    public function setLandingPage(string $landingPage): void
    {
        $this->landingPage = $landingPage;
    }

    public function getSendOrderNumber(): bool
    {
        return $this->sendOrderNumber;
    }

    public function setSendOrderNumber(bool $sendOrderNumber): void
    {
        $this->sendOrderNumber = $sendOrderNumber;
    }

    public function getOrderNumberPrefix(): ?string
    {
        return $this->orderNumberPrefix;
    }

    public function setOrderNumberPrefix(string $orderNumberPrefix): void
    {
        $this->orderNumberPrefix = $orderNumberPrefix;
    }

    public function getMerchantLocation(): string
    {
        return $this->merchantLocation;
    }

    public function setMerchantLocation(string $merchantLocation): void
    {
        if (!\in_array($merchantLocation, self::VALID_MERCHANT_LOCATIONS, true)) {
            throw new \LogicException(
                sprintf('"%s" is not a valid value for the merchant location', $merchantLocation)
            );
        }
        $this->merchantLocation = $merchantLocation;
    }

    public function getEcsDetailEnabled(): bool
    {
        return $this->ecsDetailEnabled;
    }

    public function setEcsDetailEnabled(bool $ecsDetailEnabled): void
    {
        $this->ecsDetailEnabled = $ecsDetailEnabled;
    }

    public function getEcsCartEnabled(): bool
    {
        return $this->ecsCartEnabled;
    }

    public function setEcsCartEnabled(bool $ecsCartEnabled): void
    {
        $this->ecsCartEnabled = $ecsCartEnabled;
    }

    public function getEcsOffCanvasEnabled(): bool
    {
        return $this->ecsOffCanvasEnabled;
    }

    public function setEcsOffCanvasEnabled(bool $ecsOffCanvasEnabled): void
    {
        $this->ecsOffCanvasEnabled = $ecsOffCanvasEnabled;
    }

    public function getEcsLoginEnabled(): bool
    {
        return $this->ecsLoginEnabled;
    }

    public function setEcsLoginEnabled(bool $ecsLoginEnabled): void
    {
        $this->ecsLoginEnabled = $ecsLoginEnabled;
    }

    public function getEcsListingEnabled(): bool
    {
        return $this->ecsListingEnabled;
    }

    public function setEcsListingEnabled(bool $ecsListingEnabled): void
    {
        $this->ecsListingEnabled = $ecsListingEnabled;
    }

    public function getEcsButtonColor(): string
    {
        return $this->ecsButtonColor;
    }

    public function setEcsButtonColor(string $ecsButtonColor): void
    {
        $this->ecsButtonColor = $ecsButtonColor;
    }

    public function getEcsButtonShape(): string
    {
        return $this->ecsButtonShape;
    }

    public function setEcsButtonShape(string $ecsButtonShape): void
    {
        $this->ecsButtonShape = $ecsButtonShape;
    }

    public function getEcsSubmitCart(): bool
    {
        return $this->ecsSubmitCart;
    }

    public function setEcsSubmitCart(bool $ecsSubmitCart): void
    {
        $this->ecsSubmitCart = $ecsSubmitCart;
    }

    public function getEcsButtonLanguageIso(): ?string
    {
        return $this->ecsButtonLanguageIso;
    }

    public function setEcsButtonLanguageIso(string $ecsButtonLanguageIso): void
    {
        $this->ecsButtonLanguageIso = $ecsButtonLanguageIso;
    }

    public function getSpbCheckoutEnabled(): bool
    {
        return $this->spbCheckoutEnabled;
    }

    public function setSpbCheckoutEnabled(bool $spbCheckoutEnabled): void
    {
        $this->spbCheckoutEnabled = $spbCheckoutEnabled;
    }

    public function getSpbAlternativePaymentMethodsEnabled(): bool
    {
        return $this->spbAlternativePaymentMethodsEnabled;
    }

    public function setSpbAlternativePaymentMethodsEnabled(bool $spbAlternativePaymentMethodsEnabled): void
    {
        $this->spbAlternativePaymentMethodsEnabled = $spbAlternativePaymentMethodsEnabled;
    }

    public function getSpbButtonColor(): string
    {
        return $this->spbButtonColor;
    }

    public function setSpbButtonColor(string $spbButtonColor): void
    {
        $this->spbButtonColor = $spbButtonColor;
    }

    public function getSpbButtonShape(): string
    {
        return $this->spbButtonShape;
    }

    public function setSpbButtonShape(string $spbButtonShape): void
    {
        $this->spbButtonShape = $spbButtonShape;
    }

    public function getSpbButtonLanguageIso(): ?string
    {
        return $this->spbButtonLanguageIso;
    }

    public function setSpbButtonLanguageIso(string $spbButtonLanguageIso): void
    {
        $this->spbButtonLanguageIso = $spbButtonLanguageIso;
    }

    public function getPlusCheckoutEnabled(): bool
    {
        return $this->plusCheckoutEnabled;
    }

    public function setPlusCheckoutEnabled(bool $plusCheckoutEnabled): void
    {
        $this->plusCheckoutEnabled = $plusCheckoutEnabled;
    }

    public function getPlusOverwritePaymentName(): ?string
    {
        return $this->plusOverwritePaymentName;
    }

    public function setPlusOverwritePaymentName(string $plusOverwritePaymentName): void
    {
        $this->plusOverwritePaymentName = $plusOverwritePaymentName;
    }

    public function getPlusExtendPaymentDescription(): ?string
    {
        return $this->plusExtendPaymentDescription;
    }

    public function setPlusExtendPaymentDescription(string $plusExtendPaymentDescription): void
    {
        $this->plusExtendPaymentDescription = $plusExtendPaymentDescription;
    }
}
