<?php declare(strict_types=1);

namespace Swag\PayPal\Webhook\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class WebhookException extends ShopwareHttpException
{
    /**
     * @see WebhookEventTypes
     *
     * @var string
     */
    private $eventType;

    public function __construct(string $eventType, string $message, array $parameters = [])
    {
        $this->eventType = $eventType;
        parent::__construct($message, $parameters);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    /**
     * @see WebhookEventTypes
     */
    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__WEBHOOK';
    }
}
