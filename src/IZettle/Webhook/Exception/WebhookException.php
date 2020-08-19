<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Webhook\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class WebhookException extends ShopwareHttpException
{
    /**
     * @see WebhookEventNames
     *
     * @var string
     */
    private $eventName;

    public function __construct(string $eventName, string $message, array $parameters = [])
    {
        $this->eventName = $eventName;
        parent::__construct($message, $parameters);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    /**
     * @see WebhookEventNames
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL_IZETTLE__WEBHOOK';
    }
}
