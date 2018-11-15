<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Webhook\Exception;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WebhookOrderTransactionNotFoundException extends WebhookException
{
    protected $code = 'SWAG-PAYPAL-WEBHOOK-ORDER-TRANSACTION-NOT-FOUND-EXCEPTION';

    public function __construct(string $payPalTransactionId, string $eventType, $code = 0, Throwable $previous = null)
    {
        $message = sprintf(
            '[PayPal %s Webhook] Could not find associated order with the PayPal ID "%s"',
            $eventType,
            $payPalTransactionId
        );
        parent::__construct($eventType, $message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
