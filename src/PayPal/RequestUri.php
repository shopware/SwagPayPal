<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal;

final class RequestUri
{
    public const PAYMENT_RESOURCE = 'payments/payment';
    public const SALE_RESOURCE = 'payments/sale';
    public const AUTHORIZATION_RESOURCE = 'payments/authorization';
    public const ORDERS_RESOURCE = 'payments/orders';
    public const CAPTURE_RESOURCE = 'payments/capture';

    public const TOKEN_RESOURCE = 'oauth2/token';

    public const WEBHOOK_RESOURCE = 'notifications/webhooks';

    private function __construct()
    {
    }
}
