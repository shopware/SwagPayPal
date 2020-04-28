<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal;

final class RequestUri
{
    public const PAYMENT_RESOURCE = 'payments/payment';
    public const SALE_RESOURCE = 'payments/sale';
    public const AUTHORIZATION_RESOURCE = 'payments/authorization';
    public const ORDERS_RESOURCE = 'payments/orders';
    public const CAPTURE_RESOURCE = 'payments/capture';

    public const TOKEN_RESOURCE = 'oauth2/token';

    public const CREDENTIALS_RESOURCE = 'customer/partners/%s/merchant-integrations/credentials';

    public const WEBHOOK_RESOURCE = 'notifications/webhooks';

    public const IZETTLE_TOKEN_RESOURCE = 'token';

    private function __construct()
    {
    }
}
