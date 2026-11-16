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
 * Events PayPal created after the shop's last webhook. Every webhook of an app gets every event,
 * so these didn't arrive, or not yet: PayPal retries for 3 days.
 *
 * @internal
 */
#[OA\Schema(
    schema: 'swag_paypal_diagnostics_webhook_missed_events',
    description: 'Events PayPal created after the last webhook the shop received',
    required: ['count', 'more', 'newestAt', 'newestEventType'],
    properties: [
        new OA\Property(property: 'count', type: 'integer'),
        new OA\Property(property: 'more', description: 'Whether PayPal has more events than the counted ones', type: 'boolean'),
        new OA\Property(property: 'newestAt', description: 'When PayPal created the newest event (RFC 3339)', type: 'string'),
        new OA\Property(property: 'newestEventType', description: 'For example `PAYMENT.CAPTURE.COMPLETED`', type: 'string'),
    ],
)]
#[Package('checkout')]
final class MissedEvents extends Struct
{
    public function __construct(
        public readonly int $count,
        public readonly bool $more,
        public readonly string $newestAt,
        public readonly string $newestEventType,
    ) {
    }
}
