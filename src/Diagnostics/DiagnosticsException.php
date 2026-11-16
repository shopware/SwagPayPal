<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics;

use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
class DiagnosticsException extends HttpException
{
    public const NO_WEBHOOK_REGISTERED = 'SWAG_PAYPAL_DIAGNOSTICS__NO_WEBHOOK_REGISTERED';
    public const NO_APP_URL = 'SWAG_PAYPAL_DIAGNOSTICS__NO_APP_URL';

    public static function noWebhookRegistered(string $salesChannelId): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::NO_WEBHOOK_REGISTERED,
            'No webhook is registered for the Sales Channel "{{ salesChannelId }}"',
            ['salesChannelId' => $salesChannelId],
        );
    }

    public static function noAppUrl(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::NO_APP_URL,
            'The APP_URL is not set or has no host',
        );
    }
}
