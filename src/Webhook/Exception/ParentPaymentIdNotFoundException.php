<?php
declare(strict_types=1);

namespace Swag\PayPal\Webhook\Exception;

use Symfony\Component\HttpFoundation\Response;

class ParentPaymentIdNotFoundException extends WebhookException
{
    public function __construct(string $eventType)
    {
        parent::__construct(
            $eventType,
            '[PayPal {{ eventType }} Webhook] Could not find a parent payment id, perhaps it\'s a subscription?',
            [
                'eventType' => $eventType
            ]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__WEBHOOK_PARENT_PAYMENT_ID_NOT_FOUND';
    }
}