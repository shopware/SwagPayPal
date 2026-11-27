<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Reachability;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Swag\PayPal\Diagnostics\Severity;

/**
 * @internal
 */
#[OA\Schema(
    schema: 'swag_paypal_diagnostics_reachability',
    description: 'The answer to a test request sent to a callback URL, like PayPal sends it, combined with the address its host resolves to',
    required: ['url', 'error', 'severity', 'addresses', 'statusCode', 'location', 'detail'],
    properties: [
        new OA\Property(property: 'url', description: 'The tested URL, its throw-away token replaced by `<token>`', type: 'string'),
        new OA\Property(property: 'error', description: 'Null when PayPal can reach the URL', type: 'string', enum: ['host_unresolved', 'host_local', 'certificate_invalid', 'redirected', 'denied', 'not_found', 'server_error', 'unexpected_answer', 'host_private', 'timeout', 'connection_failed'], nullable: true),
        new OA\Property(property: 'severity', description: 'Null when PayPal can reach the URL. `warning` when the shop may still be reachable from PayPal, as servers often can\'t reach their own public URL or resolve their own domain to a private address', type: 'string', enum: ['error', 'warning'], nullable: true),
        new OA\Property(property: 'addresses', description: 'The addresses of the URL\'s host', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'statusCode', description: 'The status code of an answer that doesn\'t come from the plugin', type: 'integer', nullable: true),
        new OA\Property(property: 'location', description: 'The redirect target', type: 'string', nullable: true),
        new OA\Property(property: 'detail', description: 'The HTTP client\'s message when the request failed, for support', type: 'string', nullable: true),
    ],
)]
#[Package('checkout')]
final class ReachabilityResult extends Struct
{
    public readonly ?Severity $severity;

    /**
     * @param list<string> $addresses
     */
    public function __construct(
        public readonly string $url,
        public readonly ?ReachabilityError $error,
        public readonly array $addresses,
        public readonly ?int $statusCode = null,
        public readonly ?string $location = null,
        public readonly ?string $detail = null,
    ) {
        $this->severity = $error?->severity();
    }
}
