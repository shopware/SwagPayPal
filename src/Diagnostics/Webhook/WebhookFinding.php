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
use Swag\PayPal\Diagnostics\Severity;

/**
 * @internal
 */
#[OA\Schema(
    schema: 'swag_paypal_diagnostics_webhook_finding',
    description: 'Something that keeps PayPal from reaching the shop with webhooks, or makes it unreliable',
    required: ['type', 'severity'],
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['registration_missing', 'registration_invalid', 'registration_outdated', 'registration_unknown', 'last_webhook_failed', 'unknown_transaction', 'missed_events']),
        new OA\Property(property: 'severity', type: 'string', enum: ['error', 'warning', 'notice', 'unknown']),
    ],
)]
#[Package('checkout')]
final class WebhookFinding extends Struct
{
    public readonly Severity $severity;

    public function __construct(public readonly WebhookFindingType $type)
    {
        $this->severity = $type->severity();
    }
}
