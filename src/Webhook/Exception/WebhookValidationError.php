<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class WebhookValidationError extends ShopwareHttpException
{
    public function __construct(
        protected readonly string $webhookUrl,
    ) {
        $message = 'Provided webhook URL "{{ webhookUrl }}" is invalid';

        $url = \parse_url($webhookUrl, \PHP_URL_HOST);
        if (\is_string($url) && (\str_ends_with($url, 'localhost') || \str_ends_with($url, '127.0.0.1'))) {
            $message = 'It\'s not allowed to register a webhook on localhost';
        }

        parent::__construct(
            $message,
            ['webhookUrl' => $webhookUrl]
        );
    }

    public function getWebhookUrl(): string
    {
        return $this->webhookUrl;
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__WEBHOOK_VALIDATION_ERROR';
    }
}
