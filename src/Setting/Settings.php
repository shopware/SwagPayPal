<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting;

use Monolog\Logger;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;

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
    public const ECS_SUBMIT_CART = self::SYSTEM_CONFIG_DOMAIN . 'ecsSubmitCart';
    public const ECS_BUTTON_LANGUAGE_ISO = self::SYSTEM_CONFIG_DOMAIN . 'ecsButtonLanguageIso';
    public const SPB_BUTTON_COLOR = self::SYSTEM_CONFIG_DOMAIN . 'spbButtonColor';
    public const SPB_BUTTON_SHAPE = self::SYSTEM_CONFIG_DOMAIN . 'spbButtonShape';
    public const SPB_BUTTON_LANGUAGE_ISO = self::SYSTEM_CONFIG_DOMAIN . 'spbButtonLanguageIso';
    public const SPB_SHOW_PAY_LATER = self::SYSTEM_CONFIG_DOMAIN . 'spbShowPayLater';
    public const PUI_CUSTOMER_SERVICE_INSTRUCTIONS = self::SYSTEM_CONFIG_DOMAIN . 'puiCustomerServiceInstructions';
    public const INSTALLMENT_BANNER_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'installmentBannerEnabled';
    public const LOGGING_LEVEL = self::SYSTEM_CONFIG_DOMAIN . 'loggingLevel';

    /**
     * @deprecated tag:v6.0.0 - Will be removed without replacement.
     */
    public const SPB_CHECKOUT_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'spbCheckoutEnabled';

    /**
     * @deprecated tag:v6.0.0 - Will be removed without replacement.
     */
    public const SPB_ALTERNATIVE_PAYMENT_METHODS_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'spbAlternativePaymentMethodsEnabled';

    /**
     * @deprecated tag:v6.0.0 - Will be removed without replacement.
     */
    public const MERCHANT_LOCATION = self::SYSTEM_CONFIG_DOMAIN . 'merchantLocation';

    /**
     * @deprecated tag:v6.0.0 - Will be removed without replacement.
     */
    public const PLUS_CHECKOUT_ENABLED = self::SYSTEM_CONFIG_DOMAIN . 'plusCheckoutEnabled';

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
        self::ECS_LISTING_ENABLED => true,
        self::ECS_BUTTON_COLOR => 'gold',
        self::ECS_BUTTON_SHAPE => 'rect',
        self::ECS_SUBMIT_CART => true,
        self::SPB_CHECKOUT_ENABLED => false,
        self::SPB_ALTERNATIVE_PAYMENT_METHODS_ENABLED => false,
        self::SPB_BUTTON_COLOR => 'gold',
        self::SPB_BUTTON_SHAPE => 'rect',
        self::SPB_SHOW_PAY_LATER => true,
        self::PLUS_CHECKOUT_ENABLED => false,
        self::INSTALLMENT_BANNER_ENABLED => true,
        self::LOGGING_LEVEL => Logger::WARNING,
        self::PUI_CUSTOMER_SERVICE_INSTRUCTIONS => 'Details zum Kundenservice finden Sie auf unserer Webseite',
    ];

    public const MERCHANT_LOCATION_GERMANY = 'germany';
    public const MERCHANT_LOCATION_OTHER = 'other';
    public const VALID_MERCHANT_LOCATIONS = [
        self::MERCHANT_LOCATION_GERMANY,
        self::MERCHANT_LOCATION_OTHER,
    ];

    private function __construct()
    {
    }
}
