<?php declare(strict_types=1);

namespace Swag\PayPal\Webhook\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class WebhookAlreadyExistsException extends ShopwareHttpException
{
    public function __construct(string $webhookUrl)
    {
        parent::__construct(
            'WebhookUrl "{{ webhookUrl }}" already exists',
            ['webhookUrl' => $webhookUrl]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__WEBHOOK_ALREADY_EXISTS';
    }
}
