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

class WebhookException extends ShopwareHttpException
{
    protected $code = 'SWAG-PAYPAL-WEBHOOK-EXCEPTION';

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
