<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Log;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\PayPalSDK\Exception\ApiException;
use Shopware\PayPalSDK\Exception\ErrorApiException;
use Shopware\PayPalSDK\Struct\V1\Webhook\Event;
use Shopware\PayPalSDK\Struct\V2\Common\Money;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Authorization;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Capture;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\AgenticCommerce\Exception\AgentHttpException;
use Swag\PayPal\RestApi\Exception\PayPalApiException;

/**
 * Builds every log context, so each value is transformed once, e.g. `Log::context(...$logContext, code: LogCode::X, payment: $capture)`.
 * Values shared by several lines are an array keyed by the constants below, spread in, PHPStan checks it like named arguments.
 * Each key is a constant and a parameter of `context()` with the same name.
 *
 * @internal
 */
#[Package('checkout')]
final class Log
{
    // General
    public const CODE = 'code';
    public const EXCEPTION = 'exception';
    public const ERROR_CODE = 'errorCode';
    public const SALES_CHANNEL_ID = 'salesChannelId';
    public const ROUTE = 'route';

    // Objects `context()` expands into the keys below
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
    public const PAYPAL_ERROR = 'paypalError';
    public const PAYPAL_ISSUES = 'paypalIssues';
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

    // Redacted by `context()`, call sites pass raw values
    public const REQUEST = 'request';
    public const RESPONSE = 'response';
    public const PAYLOAD = 'payload';
    public const HTTP_PATH = 'httpPath';

    // unvalidated input, e.g. of the browser or of a webhook event with an invalid token
    private const CUT_KEYS = [
        self::CLIENT_CODE,
        self::CLIENT_ERROR,
        self::CLIENT_STEP,
        self::PAGE_TYPE,
        self::WEBHOOK_EVENT_ID,
        self::WEBHOOK_EVENT_TYPE,
    ];
    private const CUT_LENGTH = 200;

    /**
     * Entities and PayPal structs become their IDs, bodies and token paths are redacted, unvalidated input is cut, the exception
     * is normalized with its error code and PayPal error, and empty values are dropped, but not `false` or `0`.
     * Explicit values win over those of an object or the exception. Never throws, as logging must not fail a payment.
     *
     * @param LogCode::*|null $code
     * @param list<string>|null $paypalIssues
     * @param list<string>|null $handlers
     * @param string|array<array-key, mixed>|null $request a raw JSON or form body
     * @param string|array<array-key, mixed>|null $response a raw JSON or form body
     * @param string|array<array-key, mixed>|null $payload a raw webhook body
     *
     * @return array<string, mixed>
     */
    public static function context(
        // General
        ?string $code = null,
        ?\Throwable $exception = null,
        ?string $errorCode = null,
        ?string $salesChannelId = null,
        // Objects, expanded into the keys below
        ?OrderTransactionEntity $orderTransaction = null,
        ?Order $paypalOrder = null,
        Capture|Refund|Authorization|null $payment = null,
        ?Event $webhookEvent = null,
        // Order and payment
        ?string $orderId = null,
        ?string $orderNumber = null,
        ?string $orderTransactionId = null,
        ?string $orderDeliveryId = null,
        ?string $paymentMethod = null,
        ?string $fromState = null,
        ?string $toState = null,
        ?string $currency = null,
        ?string $customerId = null,
        ?string $customerNumber = null,
        ?string $lineItemId = null,
        ?string $carrier = null,
        ?string $trackingCodes = null,
        ?string $shippingMethodName = null,
        // PayPal
        ?string $paypalOrderId = null,
        ?string $paypalDebugId = null,
        ?string $paypalRequestId = null,
        ?string $paypalError = null,
        ?array $paypalIssues = null,
        ?string $partnerAttributionId = null,
        ?string $captureId = null,
        ?string $authorizationId = null,
        // Webhooks
        ?string $webhookId = null,
        ?string $webhookEventId = null,
        ?string $webhookEventType = null,
        // API
        ?string $httpMethod = null,
        ?int $httpStatus = null,
        // Storefront and Express Checkout
        ?string $clientCode = null,
        ?string $clientError = null,
        ?string $clientStep = null,
        ?string $pageType = null,
        ?bool $fatal = null,
        ?array $handlers = null,
        ?string $callbackHost = null,
        ?string $countryCode = null,
        ?int $shippingOptionCount = null,
        ?string $selectedShippingMethodId = null,
        ?bool $contextSwitched = null,
        ?bool $guest = null,
        // Settings
        ?string $changedKeys = null,
        ?bool $sandbox = null,
        ?string $missingSetting = null,
        ?string $localeCode = null,
        ?string $fallbackLocaleCode = null,
        // Agentic commerce
        ?string $endpoint = null,
        ?string $resultMessage = null,
        // Redacted
        ?string $httpPath = null,
        string|array|null $request = null,
        string|array|null $response = null,
        string|array|null $payload = null,
    ): array {
        // the parameter names are the keys
        $values = \get_defined_vars();

        try {
            unset($values['exception'], $values['orderTransaction'], $values['paypalOrder'], $values['payment'], $values['webhookEvent']);

            $values[self::HTTP_PATH] = $httpPath !== null ? LogRedactor::redactPath($httpPath) : null;
            $values[self::REQUEST] = $request !== null ? LogRedactor::redactBody($request) : null;
            $values[self::RESPONSE] = $response !== null ? LogRedactor::redactBody($response) : null;
            $values[self::PAYLOAD] = $payload !== null ? LogRedactor::redactBody($payload) : null;
            foreach (self::CUT_KEYS as $key) {
                $values[$key] = \is_string($values[$key]) ? \mb_substr($values[$key], 0, self::CUT_LENGTH) : null;
            }

            $context = [
                ...self::orderTransaction($orderTransaction),
                ...($paypalOrder !== null ? self::paypalOrder($paypalOrder) : []),
                ...($payment !== null ? self::payment($payment) : []),
                ...($webhookEvent !== null ? self::webhookEvent($webhookEvent) : []),
                ...self::withoutEmpty($values),
            ];

            if ($exception !== null) {
                $context += self::withoutEmpty(self::paypalError($exception));
                $context[self::ERROR_CODE] ??= self::errorCode($exception);
                $context[self::EXCEPTION] = self::normalizeException($exception);
            }

            return $context;
        } catch (\Throwable) {
            // the bare minimum, as normalizing or redacting may be what failed
            return self::withoutEmpty([
                self::CODE => $code,
                self::EXCEPTION => $exception !== null ? ['class' => $exception::class, 'message' => $exception->getMessage()] : null,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private static function withoutEmpty(array $values): array
    {
        return \array_filter($values, static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return array<string, scalar>
     */
    private static function orderTransaction(?OrderTransactionEntity $transaction): array
    {
        if ($transaction === null) {
            return [];
        }

        // the getters of an entity loaded without some of its fields throw
        try {
            $context = [self::ORDER_TRANSACTION_ID => $transaction->getId()];

            if ($order = $transaction->getOrder()) {
                $context = [...$context, ...self::order($order)];
            }

            if (($paymentMethod = $transaction->getPaymentMethod()) && $technicalName = $paymentMethod->getTechnicalName()) {
                $context[self::PAYMENT_METHOD] = $technicalName;
            }

            return $context;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, scalar>
     */
    private static function order(OrderEntity $order): array
    {
        $context = [self::ORDER_ID => $order->getId()];

        if ($order->getOrderNumber() !== null) {
            $context[self::ORDER_NUMBER] = $order->getOrderNumber();
        }

        $context[self::SALES_CHANNEL_ID] = $order->getSalesChannelId();

        return $context;
    }

    /**
     * A minimal PayPal response may lack fields, so every getter is guarded.
     *
     * @return array<string, scalar>
     */
    private static function paypalOrder(Order $order): array
    {
        $context = [];

        if ($order->isset('id')) {
            $context[self::PAYPAL_ORDER_ID] = $order->getId();
        }

        if ($order->isset('intent')) {
            $context[self::INTENT] = $order->getIntent();
        }

        if (!$order->isset('purchaseUnits') || !$purchaseUnit = $order->getPurchaseUnits()->first()) {
            return $context;
        }

        if ($purchaseUnit->isset('amount')) {
            $context = [...$context, ...self::amount($purchaseUnit->getAmount())];
        }

        if ($purchaseUnit->isset('invoiceId') && $purchaseUnit->getInvoiceId() !== null) {
            $context[self::INVOICE_ID] = $purchaseUnit->getInvoiceId();
        }

        return $context;
    }

    /**
     * @return array<string, string>
     */
    private static function payment(Capture|Refund|Authorization $payment): array
    {
        $context = [];
        [$idKey, $statusKey] = match (true) {
            $payment instanceof Capture => [self::CAPTURE_ID, self::CAPTURE_STATUS],
            $payment instanceof Refund => [self::REFUND_ID, self::REFUND_STATUS],
            $payment instanceof Authorization => [self::AUTHORIZATION_ID, self::AUTHORIZATION_STATUS],
        };

        if ($payment->isset('id')) {
            $context[$idKey] = $payment->getId();
        }

        if ($payment->isset('status')) {
            $context[$statusKey] = $payment->getStatus();
        }

        return [...$context, ...self::amount($payment->isset('amount') ? $payment->getAmount() : null)];
    }

    /**
     * @return array<string, string>
     */
    private static function amount(?Money $amount): array
    {
        $context = [];

        if ($amount?->isset('value')) {
            $context[self::AMOUNT] = $amount->getValue();
        }

        if ($amount?->isset('currencyCode')) {
            $context[self::CURRENCY] = $amount->getCurrencyCode();
        }

        return $context;
    }

    /**
     * @return array<string, string>
     */
    private static function webhookEvent(Event $event): array
    {
        $context = [];

        if ($event->isset('id')) {
            $context[self::WEBHOOK_EVENT_ID] = $event->getId();
        }

        if ($event->isset('eventType')) {
            $context[self::WEBHOOK_EVENT_TYPE] = $event->getEventType();
        }

        return $context;
    }

    private static function errorCode(\Throwable $exception): string
    {
        return match (true) {
            $exception instanceof ShopwareHttpException => $exception->getErrorCode(),
            $exception instanceof ApiException => PayPalApiException::from($exception)->getErrorCode(),
            default => (new \ReflectionClass($exception))->getShortName(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function paypalError(\Throwable $exception): array
    {
        $apiException = null;
        $wrapper = null;
        for ($e = $exception; $e instanceof \Throwable; $e = $e->getPrevious()) {
            $apiException ??= $e instanceof ApiException ? $e : null;
            $wrapper ??= $e instanceof PayPalApiException ? $e : null;
        }

        if ($apiException !== null) {
            $issues = $apiException instanceof ErrorApiException ? \array_values($apiException->getDetails()->getIssues()) : [];

            return [
                self::HTTP_STATUS => $apiException->getStatusCode(),
                self::PAYPAL_ERROR => $apiException->getErrorCode(),
                self::PAYPAL_ISSUES => $issues ?: null,
                self::PAYPAL_DEBUG_ID => $apiException instanceof ErrorApiException ? $apiException->debugId : null,
            ];
        }

        if ($wrapper !== null) {
            return [
                self::HTTP_STATUS => $wrapper->getStatusCode(),
                self::PAYPAL_ERROR => $wrapper->getName(),
                self::PAYPAL_ISSUES => $wrapper->getIssue() !== null ? [$wrapper->getIssue()] : null,
            ];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeException(\Throwable $e): array
    {
        $normalized = ['class' => $e::class, 'message' => $e->getMessage()];

        if ($e instanceof ShopwareHttpException) {
            $normalized['errorCode'] = $e->getErrorCode();
            $normalized['parameters'] = LogRedactor::redactBody($e->getParameters());
        }

        if ($e instanceof AgentHttpException) {
            $normalized['details'] = LogRedactor::redactBody($e->getDetails()->jsonSerialize());
        }

        if ($e instanceof ConstraintViolationException) {
            foreach ($e->getViolations() as $violation) {
                $normalized['violations'][] = (string) $violation;
            }
        }

        $normalized['file'] = $e->getFile();
        $normalized['line'] = $e->getLine();

        // the SDK exception's facts are in the context already
        $previous = $e->getPrevious();
        if ($previous !== null && !$previous instanceof ApiException) {
            $normalized['previous'] = self::normalizeException($previous);
        }

        return $normalized;
    }
}
