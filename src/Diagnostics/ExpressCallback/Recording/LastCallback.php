<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\ExpressCallback\Recording;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @internal
 */
#[OA\Schema(
    schema: 'swag_paypal_diagnostics_express_callback_last_callback',
    description: 'The last shipping callback PayPal sent to the Sales Channel',
    required: ['at', 'outcome'],
    properties: [
        new OA\Property(property: 'at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'outcome', description: '`answered` when the shop sent the recalculated prices, `rejected` when it can\'t ship to the address or with the shipping method, `failed` on a server error', type: 'string', enum: ['answered', 'rejected', 'failed']),
    ],
)]
#[Package('checkout')]
final class LastCallback extends Struct
{
    public function __construct(
        public readonly \DateTimeImmutable $at,
        public readonly CallbackOutcome $outcome,
    ) {
    }
}
