<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Log;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
final class LogKey
{
    // General
    public const CODE = 'code';
    public const EXCEPTION = 'exception';
    public const ERROR_CODE = 'errorCode';
    public const SALES_CHANNEL_ID = 'salesChannelId';
    public const AREA = 'area';

    // Objects the `PayPalLogProcessor` expands into the keys below
    public const ORDER_TRANSACTION = 'orderTransaction';
    public const PAYPAL_ORDER = 'paypalOrder';
    public const PAYMENT = 'payment';
    public const WEBHOOK_EVENT = 'webhookEvent';

    // Order and payment
    public const ORDER_ID = 'orderId';
    public const ORDER_NUMBER = 'orderNumber';
    public const ORDER_TRANSACTION_ID = 'orderTransactionId';
    public const ORDER_DELIVERY_ID = 'orderDeliveryId';
    public const PAYMENT_METHOD = 'paymentMethod';
    public const FROM_STATE = 'fromState';
    public const TO_STATE = 'toState';
    public const AMOUNT = 'amount';
    public const CURRENCY = 'currency';
    public const CUSTOMER_ID = 'customerId';
    public const CUSTOMER_NUMBER = 'customerNumber';
    public const USER_ID = 'userId';
    public const LINE_ITEM_ID = 'lineItemId';
    public const CARRIER = 'carrier';
    public const TRACKING_CODES = 'trackingCodes';
    public const SHIPPING_METHOD_NAME = 'shippingMethodName';

    // PayPal
    public const PAYPAL_ORDER_ID = 'paypalOrderId';
    public const PAYPAL_DEBUG_ID = 'paypalDebugId';
    public const PAYPAL_REQUEST_ID = 'paypalRequestId';
    public const PAYPAL_ISSUE = 'paypalIssue';
    public const INTENT = 'intent';
    public const INVOICE_ID = 'invoiceId';
    public const PARTNER_ATTRIBUTION_ID = 'partnerAttributionId';
    public const CAPTURE_ID = 'captureId';
    public const CAPTURE_STATUS = 'captureStatus';
    public const AUTHORIZATION_ID = 'authorizationId';
    public const AUTHORIZATION_STATUS = 'authorizationStatus';
    public const REFUND_ID = 'refundId';
    public const REFUND_STATUS = 'refundStatus';

    // Webhooks
    public const WEBHOOK_ID = 'webhookId';
    public const WEBHOOK_EVENT_ID = 'webhookEventId';
    public const WEBHOOK_EVENT_TYPE = 'webhookEventType';

    // API
    public const HTTP_METHOD = 'httpMethod';
    public const HTTP_STATUS = 'httpStatus';

    // Storefront and Express Checkout
    public const CLIENT_CODE = 'clientCode';
    public const CLIENT_ERROR = 'clientError';
    public const CLIENT_STEP = 'clientStep';
    public const PAGE_TYPE = 'pageType';
    public const FATAL = 'fatal';
    public const HANDLERS = 'handlers';
    public const CALLBACK_HOST = 'callbackHost';
    public const COUNTRY_CODE = 'countryCode';
    public const SHIPPING_OPTION_COUNT = 'shippingOptionCount';
    public const SELECTED_SHIPPING_METHOD_ID = 'selectedShippingMethodId';
    public const CONTEXT_SWITCHED = 'contextSwitched';
    public const GUEST = 'guest';

    // Settings
    public const CHANGED_KEYS = 'changedKeys';
    public const SANDBOX = 'sandbox';
    public const MISSING_SETTING = 'missingSetting';
    public const LOCALE_CODE = 'localeCode';
    public const FALLBACK_LOCALE_CODE = 'fallbackLocaleCode';

    // Agentic commerce
    public const ENDPOINT = 'endpoint';
    public const RESULT_MESSAGE = 'resultMessage';

    // Redacted by the `PayPalLogProcessor`, call sites pass raw values
    public const REQUEST = 'request';
    public const RESPONSE = 'response';
    public const PAYLOAD = 'payload';
    public const HTTP_PATH = 'httpPath';
}
