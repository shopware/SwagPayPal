<?php declare(strict_types=1);

namespace Swag\PayPal\Webhook\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class WebhookIdInvalidException extends ShopwareHttpException
{
    protected $code = 'SWAG-PAYPAL-WEBHOOK-ID-INVALID-EXCEPTION';

    public function __construct(string $webhookId)
    {
        parent::__construct('Webhook with ID "{{ webhookId }}" is invalid', ['webhookId' => $webhookId]);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__WEBHOOK_ID_INVALID';
    }
}
