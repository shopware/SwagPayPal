<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1;

final class RequestUriV1
{
    public const PAYMENT_RESOURCE = 'v1/payments/payment';
    public const SALE_RESOURCE = 'v1/payments/sale';
    public const AUTHORIZATION_RESOURCE = 'v1/payments/authorization';
    public const ORDERS_RESOURCE = 'v1/payments/orders';
    public const CAPTURE_RESOURCE = 'v1/payments/capture';

    public const TOKEN_RESOURCE = 'v1/oauth2/token';

    public const CREDENTIALS_RESOURCE = 'v1/customer/partners/%s/merchant-integrations/credentials';

    public const WEBHOOK_RESOURCE = 'v1/notifications/webhooks';

    private function __construct()
    {
    }
}
