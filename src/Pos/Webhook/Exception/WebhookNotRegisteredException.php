<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Webhook\Exception;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class WebhookNotRegisteredException extends ShopwareHttpException
{
    public function __construct(string $salesChannelId)
    {
        parent::__construct('Webhook for Sales Channel "{{ salesChannel }}" could not be registered', [
            'salesChannel' => $salesChannelId,
        ]);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_SERVICE_UNAVAILABLE;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL_POS__WEBHOOK_NOT_REGISTERED';
    }
}
