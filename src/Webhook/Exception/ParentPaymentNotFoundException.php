<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Exception;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class ParentPaymentNotFoundException extends WebhookException
{
    public function __construct(string $eventType)
    {
        parent::__construct(
            $eventType,
            '[PayPal {{ eventType }} Webhook] Could not find parent payment ID',
            ['eventType' => $eventType]
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'SWAG_PAYPAL__WEBHOOK_PARENT_PAYMENT_NOT_FOUND';
    }
}
