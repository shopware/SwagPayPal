<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Webhook\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WebhookIdInvalidException extends ShopwareHttpException
{
    protected $code = 'SWAG-PAYPAL-WEBHOOK-ID-INVALID-EXCEPTION';

    public function __construct(string $webhookId, $code = 0, Throwable $previous = null)
    {
        $message = sprintf('Webhook with ID "%s" is invalid', $webhookId);
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
