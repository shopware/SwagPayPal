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
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\LastCallback;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[OA\Schema(
    schema: 'swag_paypal_diagnostics_express_callback_result',
    required: ['status', 'lastCallback', 'findings'],
    properties: [
        new OA\Property(property: 'status', description: '`inactive` without PayPal or Express Checkout buttons, `local_environment` and `disabled` when the plugin doesn\'t ask PayPal for callbacks', type: 'string', enum: ['inactive', 'local_environment', 'disabled', 'active']),
        new OA\Property(property: 'lastCallback', ref: LastCallback::class, nullable: true),
        new OA\Property(property: 'findings', description: 'Empty unless the status is `active`', type: 'array', items: new OA\Items(ref: ExpressCallbackFinding::class)),
    ],
)]
#[Package('checkout')]
final class ExpressCallbackResult extends Struct
{
    /**
     * @param list<ExpressCallbackFinding> $findings
     */
    public function __construct(
        public readonly ExpressCallbackStatus $status,
        public readonly ?LastCallback $lastCallback,
        public readonly array $findings,
    ) {
    }
}
