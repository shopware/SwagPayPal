<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v2_order_payment_source_common_experience_context')]
#[Package('checkout')]
class ExperienceContext extends PayPalApiStruct
{
    public const LANDING_PAGE_TYPE_LOGIN = 'LOGIN';
    public const LANDING_PAGE_TYPE_GUEST = 'GUEST_CHECKOUT';
    public const LANDING_PAGE_TYPE_NO_PREFERENCE = 'NO_PREFERENCE';
    public const LANDING_PAGE_TYPES = [
        self::LANDING_PAGE_TYPE_LOGIN,
        self::LANDING_PAGE_TYPE_GUEST,
        self::LANDING_PAGE_TYPE_NO_PREFERENCE,
    ];

    public const SHIPPING_PREFERENCE_SET_PROVIDED_ADDRESS = 'SET_PROVIDED_ADDRESS';
    public const SHIPPING_PREFERENCE_NO_SHIPPING = 'NO_SHIPPING';
    public const SHIPPING_PREFERENCE_GET_FROM_FILE = 'GET_FROM_FILE';

    public const USER_ACTION_CONTINUE = 'CONTINUE';
    public const USER_ACTION_PAY_NOW = 'PAY_NOW';

    public const PAYMENT_METHOD_PREFERENCE_UNRESTRICTED = 'UNRESTRICTED';
    public const PAYMENT_METHOD_PREFERENCE_IMMEDIATE_REQUIRED = 'IMMEDIATE_PAYMENT_REQUIRED';

    #[OA\Property(type: 'string')]
    protected string $locale;

    #[OA\Property(type: 'string')]
    protected string $brandName;

    #[OA\Property(type: 'string')]
    protected string $logoUrl;

    #[OA\Property(type: 'string', default: self::LANDING_PAGE_TYPE_NO_PREFERENCE, enum: self::LANDING_PAGE_TYPES)]
    protected string $landingPage = self::LANDING_PAGE_TYPE_NO_PREFERENCE;

    #[OA\Property(type: 'string', default: self::SHIPPING_PREFERENCE_SET_PROVIDED_ADDRESS, enum: [self::SHIPPING_PREFERENCE_SET_PROVIDED_ADDRESS, self::SHIPPING_PREFERENCE_NO_SHIPPING, self::SHIPPING_PREFERENCE_GET_FROM_FILE])]
    protected string $shippingPreference = self::SHIPPING_PREFERENCE_SET_PROVIDED_ADDRESS;

    #[OA\Property(type: 'string', default: self::USER_ACTION_PAY_NOW, enum: [self::USER_ACTION_CONTINUE, self::USER_ACTION_PAY_NOW])]
    protected string $userAction = self::USER_ACTION_PAY_NOW;

    #[OA\Property(type: 'string')]
    protected string $returnUrl;

    #[OA\Property(type: 'string')]
    protected string $cancelUrl;

    /**
     * Only: PayPal Wallet
     */
    #[OA\Property(type: 'string', enum: [self::PAYMENT_METHOD_PREFERENCE_UNRESTRICTED, self::PAYMENT_METHOD_PREFERENCE_IMMEDIATE_REQUIRED])]
    protected string $paymentMethodPreference;

    /**
     * Only: PUI
     *
     * @var string[]
     */
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
    protected array $customerServiceInstructions;

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getBrandName(): string
    {
        return $this->brandName;
    }

    public function setBrandName(string $brandName): void
    {
        $this->brandName = $brandName;
    }

    public function getLogoUrl(): string
    {
        return $this->logoUrl;
    }

    public function setLogoUrl(string $logoUrl): void
    {
        $this->logoUrl = $logoUrl;
    }

    public function getShippingPreference(): string
    {
        return $this->shippingPreference;
    }

    public function setShippingPreference(string $shippingPreference): void
    {
        $this->shippingPreference = $shippingPreference;
    }

    public function getLandingPage(): string
    {
        return $this->landingPage;
    }

    public function setLandingPage(string $landingPage): void
    {
        $this->landingPage = $landingPage;
    }

    public function getUserAction(): string
    {
        return $this->userAction;
    }

    public function setUserAction(string $userAction): void
    {
        $this->userAction = $userAction;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(string $returnUrl): void
    {
        $this->returnUrl = $returnUrl;
    }

    public function getCancelUrl(): string
    {
        return $this->cancelUrl;
    }

    public function setCancelUrl(string $cancelUrl): void
    {
        $this->cancelUrl = $cancelUrl;
    }

    public function getPaymentMethodPreference(): string
    {
        return $this->paymentMethodPreference;
    }

    public function setPaymentMethodPreference(string $paymentMethodPreference): void
    {
        $this->paymentMethodPreference = $paymentMethodPreference;
    }

    /**
     * @return string[]
     */
    public function getCustomerServiceInstructions(): array
    {
        return $this->customerServiceInstructions;
    }

    /**
     * @param string[] $customerServiceInstructions
     */
    public function setCustomerServiceInstructions(array $customerServiceInstructions): void
    {
        $this->customerServiceInstructions = $customerServiceInstructions;
    }
}
