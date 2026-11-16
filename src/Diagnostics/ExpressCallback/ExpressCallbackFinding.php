<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\ExpressCallback;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Swag\PayPal\Diagnostics\Severity;

/**
 * @internal
 */
#[OA\Schema(
    schema: 'swag_paypal_diagnostics_express_callback_finding',
    description: 'Something that keeps PayPal from reaching the shop, or makes it unreliable',
    required: ['type', 'severity', 'host'],
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['last_callback_failed', 'untrusted_proxy', 'sandbox', 'store_api_url_unknown', 'store_api_host_unknown']),
        new OA\Property(property: 'severity', type: 'string', enum: ['error', 'warning', 'notice', 'unknown']),
        new OA\Property(property: 'host', description: 'The host of a `store_api_host_unknown` finding', type: 'string', nullable: true),
    ],
)]
#[Package('checkout')]
final class ExpressCallbackFinding extends Struct
{
    public readonly Severity $severity;

    public function __construct(
        public readonly ExpressCallbackFindingType $type,
        public readonly ?string $host = null,
    ) {
        $this->severity = $type->severity();
    }
}
