<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Webhook;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * PayPal's test events have one fixed ID per type, so a test arrived
 * when a webhook with that ID came at or after `sentAt`.
 *
 * @internal
 */
#[OA\Schema(
    schema: 'swag_paypal_diagnostics_webhook_test',
    description: 'A test webhook PayPal was asked to send',
    required: ['eventId', 'sentAt'],
    properties: [
        new OA\Property(property: 'eventId', description: 'The ID of the test event. PayPal uses the same ID for every test of an event type', type: 'string'),
        new OA\Property(property: 'sentAt', description: 'When the shop asked PayPal to send it, by the shop\'s clock like the webhooks\' arrival times', type: 'string', format: 'date-time'),
    ],
)]
#[Package('checkout')]
final class TestWebhook extends Struct
{
    public function __construct(
        public readonly string $eventId,
        public readonly \DateTimeImmutable $sentAt,
    ) {
    }
}
