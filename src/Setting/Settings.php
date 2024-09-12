<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;

#[Package('checkout')]
final class Settings
{
    public const SYSTEM_CONFIG_DOMAIN = 'SwagPayPal.settings.';

    public const CLIENT_ID = self::SYSTEM_CONFIG_DOMAIN . 'clientId';
    public const CLIENT_SECRET = self::SYSTEM_CONFIG_DOMAIN . 'clientSecret';
    public const CLIENT_ID_SANDBOX = self::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox';
    public const CLIENT_SECRET_SANDBOX = self::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox';
    public const MERCHANT_PAYER_ID = self::SYSTEM_CONFIG_DOMAIN . 'merchantPayerId';
    public const MERCHANT_PAYER_ID_SANDBOX = self::SYSTEM_CONFIG_DOMAIN . 'merchantPayerIdSandbox';
    public const SANDBOX = self::SYSTEM_CONFIG_DOMAIN . 'sandbox';
    public const INTENT = self::SYSTEM_CONFIG_DOMAIN . 'intent';
    public const SUBMIT_CART = self::SYSTEM_CONFIG_DOMAIN . 'submitCart';
    public const WEBHOOK_ID = self::SYSTEM_CONFIG_DOMAIN . 'webhookId';
    public const WEBHOOK_EXECUTE_TOKEN = self::SYSTEM_CONFIG_DOMAIN . 'webhookExecuteToken';
    public const BRAND_NAME = self::SYSTEM_CONFIG_DOMAIN . 'brandName';
    public const LANDING_PAGE = self::SYSTEM_CONFIG_DOMAIN . 'landingPage';
    public const SEND_ORDER_NUMBER = self::SYSTEM_CONFIG_DOMAIN . 'sendOrderNumber';
    public const ORDER_NUMBER_PREFIX = self::SYSTEM_CONFIG_DOMAIN . 'orderNumberPrefix';
    public const ORDER_NUMBER_SUFFIX = self::SYSTEM_CONFIG_DOMAIN . 'orderNumberSuffix';
    public const ECS_DETAIL_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'ecsDetailEnabled';
    public const ECS_CART_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'ecsCartEnabled';
    public const ECS_OFF_CANVAS_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'ecsOffCanvasEnabled';
    public const ECS_LOGIN_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'ecsLoginEnabled';
    public const ECS_LISTING_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'ecsListingEnabled';
    public const ECS_BUTTON_COLOR = self::SYSTEM_CONFIG_DOMAIN . 'ecsButtonColor';
    public const ECS_BUTTON_SHAPE = self::SYSTEM_CONFIG_DOMAIN . 'ecsButtonShape';
    public const ECS_BUTTON_LANGUAGE_ISO = self::SYSTEM_CONFIG_DOMAIN . 'ecsButtonLanguageIso';

    public const ECS_SHOW_PAY_LATER = self::SYSTEM_CONFIG_DOMAIN . 'ecsShowPayLater';
    public const SPB_BUTTON_COLOR = self::SYSTEM_CONFIG_DOMAIN . 'spbButtonColor';
    public const SPB_BUTTON_SHAPE = self::SYSTEM_CONFIG_DOMAIN . 'spbButtonShape';
    public const SPB_BUTTON_LANGUAGE_ISO = self::SYSTEM_CONFIG_DOMAIN . 'spbButtonLanguageIso';
    public const ACDC_FORCE_3DS = self::SYSTEM_CONFIG_DOMAIN . 'acdcForce3DS';
    public const PUI_CUSTOMER_SERVICE_INSTRUCTIONS = self::SYSTEM_CONFIG_DOMAIN . 'puiCustomerServiceInstructions';
    public const INSTALLMENT_BANNER_DETAIL_PAGE_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'installmentBannerDetailPageEnabled';
    public const INSTALLMENT_BANNER_CART_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'installmentBannerCartEnabled';
    public const INSTALLMENT_BANNER_OFF_CANVAS_CART_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'installmentBannerOffCanvasCartEnabled';
    public const INSTALLMENT_BANNER_LOGIN_PAGE_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'installmentBannerLoginPageEnabled';
    public const INSTALLMENT_BANNER_FOOTER_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'installmentBannerFooterEnabled';
    public const EXCLUDED_PRODUCT_IDS = self::SYSTEM_CONFIG_DOMAIN . 'excludedProductIds';
    public const EXCLUDED_PRODUCT_STREAM_IDS = self::SYSTEM_CONFIG_DOMAIN . 'excludedProductStreamIds';
    public const SPB_SHOW_PAY_LATER = self::SYSTEM_CONFIG_DOMAIN . 'spbShowPayLater';
    public const SPB_CHECKOUT_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'spbCheckoutEnabled';
    public const SPB_ALTERNATIVE_PAYMENT_METHODS_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'spbAlternativePaymentMethodsEnabled';
    public const CROSS_BORDER_MESSAGING_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'crossBorderMessagingEnabled';
    public const CROSS_BORDER_BUYER_COUNTRY = self::SYSTEM_CONFIG_DOMAIN . 'crossBorderBuyerCountry';

    /**
     * @deprecated tag:v10.0.0 - Will be removed without replacement.
     */
    public const MERCHANT_LOCATION = self::SYSTEM_CONFIG_DOMAIN . 'merchantLocation';

    /**
     * @deprecated tag:v10.0.0 - Will be removed without replacement.
     */
    public const PLUS_CHECKOUT_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'plusCheckoutEnabled';

    public const VAULTING_ENABLED_WALLET = self::SYSTEM_CONFIG_DOMAIN . 'vaultingEnabledWallet';

    public const VAULTING_ENABLED_ACDC = self::SYSTEM_CONFIG_DOMAIN . 'vaultingEnabledACDC';

    public const VAULTING_ENABLED_VENMO = self::SYSTEM_CONFIG_DOMAIN . 'vaultingEnabledVenmo';

    /**
     * @internal these may change at any time
     */
    public const DEFAULT_VALUES = [
        self::SANDBOX => false,
        self::INTENT => PaymentIntentV2::CAPTURE,
        self::SUBMIT_CART => true,
        self::LANDING_PAGE => ApplicationContext::LANDING_PAGE_TYPE_NO_PREFERENCE,
        self::SEND_ORDER_NUMBER => true,
        self::MERCHANT_LOCATION => self::MERCHANT_LOCATION_OTHER,
        self::ECS_DETAIL_ENABLED => true,
        self::ECS_CART_ENABLED => true,
        self::ECS_OFF_CANVAS_ENABLED => true,
        self::ECS_LOGIN_ENABLED => true,
        self::ECS_LISTING_ENABLED => false,
        self::ECS_BUTTON_COLOR => 'gold',
        self::ECS_BUTTON_SHAPE => 'sharp',
        self::ECS_SHOW_PAY_LATER => true,
        self::SPB_CHECKOUT_ENABLED => true,
        self::SPB_ALTERNATIVE_PAYMENT_METHODS_ENABLED => false,
        self::SPB_BUTTON_COLOR => 'gold',
        self::SPB_BUTTON_SHAPE => 'sharp',
        self::SPB_SHOW_PAY_LATER => false,
        self::PLUS_CHECKOUT_ENABLED => false,
        self::INSTALLMENT_BANNER_DETAIL_PAGE_ENABLED => true,
        self::INSTALLMENT_BANNER_CART_ENABLED => true,
        self::INSTALLMENT_BANNER_OFF_CANVAS_CART_ENABLED => true,
        self::INSTALLMENT_BANNER_LOGIN_PAGE_ENABLED => true,
        self::INSTALLMENT_BANNER_FOOTER_ENABLED => true,
        self::PUI_CUSTOMER_SERVICE_INSTRUCTIONS => 'Details zum Kundenservice finden Sie auf unserer Webseite',
        self::ACDC_FORCE_3DS => false,
        self::EXCLUDED_PRODUCT_IDS => [],
        self::EXCLUDED_PRODUCT_STREAM_IDS => [],
        self::VAULTING_ENABLED_ACDC => false,
        self::VAULTING_ENABLED_WALLET => false,
        self::VAULTING_ENABLED_VENMO => false,
        self::CROSS_BORDER_MESSAGING_ENABLED => false,
        self::CROSS_BORDER_BUYER_COUNTRY => null,
    ];

    /**
     * @deprecated tag:v10.0.0 - Will be removed without replacement.
     */
    public const MERCHANT_LOCATION_GERMANY = 'germany';

    /**
     * @deprecated tag:v10.0.0 - Will be removed without replacement.
     */
    public const MERCHANT_LOCATION_OTHER = 'other';

    /**
     * @deprecated tag:v10.0.0 - Will be removed without replacement.
     */
    public const VALID_MERCHANT_LOCATIONS = [
        self::MERCHANT_LOCATION_GERMANY,
        self::MERCHANT_LOCATION_OTHER,
    ];

    private function __construct()
    {
    }
}
